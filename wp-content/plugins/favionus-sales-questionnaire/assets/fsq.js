/* Favionus Sales Questionnaire – admin-only, vanilla JS, no build step.
 * Tree schema: {version, company, start_node, nodes:[{id, section, question, help_text,
 *   type: single|multi|input|summary, field, options:[{label,value,next}], next}], offer_mapping:[{if:{field:val|[vals]|"*"}, scope:{key:text}}]} */
(function () {
  'use strict';
  var cfg = window.FSQ || {};
  var tree = cfg.tree || {};
  var nodes = {};
  (tree.nodes || []).forEach(function (n) { nodes[n.id] = n; });
  var app = document.getElementById('fsq-app');
  if (!app) return;

  var CONFIRM_RE = /\[confirm[^\]]*\]/gi;
  var SCOPE_LABELS = { equipment: 'Echipament', heights: 'Înălțimi', power: 'Alimentare', duration: 'Durată',
    verification: 'Verificare', reporting: 'Raportare', eya: 'EYA', extras: 'Extra' };

  var history = [];            // stack of {id, section, question, field, answer(label text), value(string|array)}
  var current = tree.start_node;
  var pre = cfg.prefill || null;  // set when started from a pipeline card (?deal=ID)
  var meta = { client: pre ? pre.client || '' : '', company: pre ? pre.company || '' : '', notes: '' };

  function el(tag, attrs, children) {
    var e = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      if (k === 'text') e.textContent = attrs[k];
      else if (k === 'on') Object.keys(attrs.on).forEach(function (ev) { e.addEventListener(ev, attrs.on[ev]); });
      else e.setAttribute(k, attrs[k]);
    });
    (children || []).forEach(function (c) { if (c) e.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
    return e;
  }
  /* Render text, highlighting [confirm…] markers (internal only). */
  function rich(tag, cls, s) {
    var e = el(tag, cls ? { 'class': cls } : {}), last = 0, m;
    s = String(s || ''); CONFIRM_RE.lastIndex = 0;
    while ((m = CONFIRM_RE.exec(s))) {
      e.appendChild(document.createTextNode(s.slice(last, m.index)));
      e.appendChild(el('mark', { 'class': 'fsq-confirm', title: 'Afirmație tehnică neverificată – de confirmat înainte de a o comunica clientului', text: m[0] + ' ⚠ intern' }));
      last = m.index + m[0].length;
    }
    e.appendChild(document.createTextNode(s.slice(last)));
    return e;
  }
  function hasConfirm(s) { CONFIRM_RE.lastIndex = 0; return CONFIRM_RE.test(String(s || '')); }
  function stripConfirm(s) { return String(s || '').replace(CONFIRM_RE, '').replace(/\s{2,}/g, ' ').replace(/\s+([.,;)])/g, '$1').trim(); }

  function nextsOf(n) {
    var a = (n.options || []).map(function (o) { return o.next; });
    if (n.next) a.push(n.next);
    return a.filter(Boolean).filter(function (x, i, s) { return s.indexOf(x) === i; });
  }
  function remaining() {          // longest remaining path (cycle-safe)
    var memo = {};
    function d(id, seen) {
      var n = nodes[id];
      if (!n || n.type === 'summary' || seen[id]) return 0;
      if (memo[id] != null) return memo[id];
      seen[id] = 1; var m = 0;
      nextsOf(n).forEach(function (x) { m = Math.max(m, d(x, seen)); });
      delete seen[id];
      return (memo[id] = m + 1);
    }
    return d(current, {});
  }
  function sections() { var s = []; (tree.nodes || []).forEach(function (n) { if (n.section && s.indexOf(n.section) < 0) s.push(n.section); }); return s; }

  function go(next, answer, value) {
    var n = nodes[current];
    history.push({ id: n.id, section: n.section || '', question: n.question || '', field: n.field || n.id, answer: answer, value: value });
    current = next;
    render();
  }
  function back() { if (history.length) { current = history.pop().id; render(); } }

  function progress(done, left, label) {
    var pct = Math.round(done / Math.max(1, done + left) * 100);
    var secs = sections(), cur = nodes[current] && nodes[current].section;
    var bar = el('div', { 'class': 'fsq-progress' }, [el('div', { 'class': 'fsq-bar', style: 'width:' + pct + '%' }), el('span', { text: label })]);
    var crumbs = el('ol', { 'class': 'fsq-sections' });
    var visited = history.map(function (h) { return h.section; });
    secs.forEach(function (s) {
      crumbs.appendChild(el('li', { 'class': s === cur ? 'is-current' : (visited.indexOf(s) >= 0 ? 'is-done' : ''), text: s }));
    });
    return el('div', {}, [crumbs, bar]);
  }

  function render() {
    app.innerHTML = '';
    var n = nodes[current];
    if (!n) { app.appendChild(el('div', { 'class': 'notice notice-error', text: 'Întrebare inexistentă: ' + current })); app.appendChild(navBar()); return; }
    if (n.type === 'summary') return renderSummary(n);

    var left = remaining();
    app.appendChild(progress(history.length, left, (n.section ? n.section + ' · ' : '') + 'Întrebarea ' + (history.length + 1) + ' · ~' + Math.max(0, left - 1) + ' rămase'));
    var card = el('div', { 'class': 'fsq-card' }, [rich('h2', '', n.question)]);
    if (n.help_text) card.appendChild(rich('p', 'fsq-help', n.help_text));

    if (n.type === 'single') {
      var box = el('div', { 'class': 'fsq-options' });
      (n.options || []).forEach(function (o) {
        box.appendChild(el('button', { type: 'button', 'class': 'button button-hero fsq-opt', on: { click: function () { go(o.next || n.next, o.label, String(o.value)); } } }, [rich('span', '', o.label)]));
      });
      card.appendChild(box);
    } else if (n.type === 'multi') {
      var picks = el('div', { 'class': 'fsq-multi' }), boxes = [];
      (n.options || []).forEach(function (o) {
        var cb = el('input', { type: 'checkbox', value: String(o.value) }); boxes.push({ cb: cb, o: o });
        picks.appendChild(el('label', { 'class': 'fsq-check' }, [cb, ' ', rich('span', '', o.label)]));
      });
      card.appendChild(picks);
      card.appendChild(el('button', { type: 'button', 'class': 'button button-primary', text: 'Continuă →', on: { click: function () {
        var sel = boxes.filter(function (b) { return b.cb.checked; });
        var nxt = n.next || (sel[0] ? sel[0].o.next : null) || ((n.options || [])[0] || {}).next;
        go(nxt, sel.length ? sel.map(function (b) { return b.o.label; }).join('; ') : '—', sel.map(function (b) { return String(b.o.value); }));
      } } }));
    } else {            // input
      var input = el('textarea', { 'class': 'large-text', rows: '3' });
      if (pre && pre.fields && pre.fields[n.field]) input.value = pre.fields[n.field];
      var submit = function () { var v = input.value.trim(); go(n.next, v || '—', v); };
      input.addEventListener('keydown', function (e) { if (e.key === 'Enter' && e.ctrlKey) { e.preventDefault(); submit(); } });
      card.appendChild(el('p', {}, [input]));
      card.appendChild(el('button', { type: 'button', 'class': 'button button-primary', text: 'Continuă →', on: { click: submit } }));
      card.appendChild(el('p', { 'class': 'description', text: 'Ctrl+Enter pentru a continua.' }));
      setTimeout(function () { input.focus(); }, 0);
    }
    app.appendChild(card);
    app.appendChild(navBar());
  }

  function navBar() {
    return el('p', { 'class': 'fsq-nav' }, [
      el('button', { type: 'button', 'class': 'button', text: '← Înapoi', on: { click: back } }),
      el('button', { type: 'button', 'class': 'button-link fsq-restart', text: 'Reia de la început',
        on: { click: function () { if (confirm('Reiei chestionarul? Răspunsurile se pierd.')) { history = []; current = tree.start_node; render(); } } } })
    ]);
  }

  /* ---- offer_mapping ----
   * A rule matches when every condition in "if" matches the answer for that field:
   *   "*" = any non-empty answer; string = equal (or contained, for multi answers); array = any of.
   * Matching rules are applied in order. For each scope key values accumulate (deduplicated), except that a
   * rule is skipped for a key already set by an earlier matching rule whose conditions are a superset of its own
   * (more specific rule wins, e.g. le200+autonomous over le200). "from field X" is replaced by the labels answered for X. */
  function answers() { var a = {}; history.forEach(function (h) { a[h.field] = h; }); return a; }
  function matches(cond, h) {
    if (!h) return false;
    var vals = Array.isArray(h.value) ? h.value : [h.value];
    vals = vals.filter(function (v) { return v !== '' && v != null; });
    if (cond === '*') return vals.length > 0;
    var want = Array.isArray(cond) ? cond.map(String) : [String(cond)];
    return vals.some(function (v) { return want.indexOf(String(v)) >= 0; });
  }
  function buildScope() {
    var ans = answers(), scope = {}, setBy = {};
    (tree.offer_mapping || []).forEach(function (r) {
      var keys = Object.keys(r.if || {});
      if (!keys.every(function (k) { return matches(r.if[k], ans[k]); })) return;
      Object.keys(r.scope || {}).forEach(function (sk) {
        var prev = setBy[sk] || [];
        if (prev.some(function (pk) { return keys.length < pk.length && keys.every(function (k) { return pk.indexOf(k) >= 0; }); })) return;
        var text = String(r.scope[sk]).replace(/^from field (\w+)$/, function (_, f) { return ans[f] ? ans[f].answer : ''; });
        if (!text) return;
        (scope[sk] = scope[sk] || []);
        if (scope[sk].indexOf(text) < 0) scope[sk].push(text);
        prev.push(keys); setBy[sk] = prev;
      });
    });
    return scope;
  }

  function scopeLines(scope, client) {
    var lines = [];
    Object.keys(scope).forEach(function (k) {
      lines.push('- ' + (SCOPE_LABELS[k] || k) + ': ' + scope[k].map(function (t) {
        return client ? stripConfirm(t) + (hasConfirm(t) ? ' (de confirmat)' : '') : t;
      }).join('; '));
    });
    return lines;
  }
  /* Client-facing draft: offer scope only, [confirm] markers stripped and flagged "(de confirmat)". */
  function clientText(scope) {
    var l = ['Favionus – Propunere de scope campanie LiDAR (DRAFT)', 'Client: ' + (meta.company || '—') + (meta.client ? ' / ' + meta.client : ''),
      'Data: ' + new Date().toLocaleDateString('ro-RO'), '', 'Scope propus:'];
    l = l.concat(scopeLines(scope, true));
    var loc = answers().location; if (loc && loc.value) l.push('- Amplasament: ' + loc.value);
    if (l.join('\n').indexOf('(de confirmat)') >= 0) { l.push(''); l.push('Elementele marcate „(de confirmat)” vor fi confirmate tehnic în oferta finală.'); }
    return l.join('\n');
  }
  /* Internal record: full Q&A, notes, scope with raw [confirm] markers. */
  function internalText(scope) {
    var l = ['FAVIONUS – Fișă întâlnire vânzări (INTERN)', 'Data: ' + new Date().toLocaleString('ro-RO'),
      'Client: ' + (meta.client || '—'), 'Companie: ' + (meta.company || '—'), ''], sec = null;
    history.forEach(function (h) {
      if (h.section !== sec) { sec = h.section; l.push('[' + sec + ']'); }
      l.push('• ' + h.question); l.push('   → ' + h.answer);
    });
    l.push(''); l.push('Scope propus (intern, cu marcaje [confirm]):'); l = l.concat(scopeLines(scope, false));
    if (meta.notes) { l.push(''); l.push('Note întâlnire:'); l.push(meta.notes); }
    return l.join('\n');
  }
  function download(name, mime, content) {
    var a = el('a', { href: URL.createObjectURL(new Blob([content], { type: mime })), download: name });
    document.body.appendChild(a); a.click();
    setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
  }
  function slug() { return ((meta.company || 'fisa') + '-' + new Date().toISOString().slice(0, 10)).toLowerCase().replace(/[^a-z0-9-]+/g, '-'); }
  function copy(t, status) {
    (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(
      function () { status.textContent = 'Copiat în clipboard.'; },
      function () { var ta = el('textarea'); ta.value = t; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); status.textContent = 'Copiat.'; });
  }

  function renderSummary(n) {
    var scope = buildScope();
    app.appendChild(progress(1, 0, 'Rezumat'));
    var card = el('div', { 'class': 'fsq-card' }, [rich('h2', '', n.question || 'Rezumat')]);
    var tbody = el('tbody'), sec = null;
    history.forEach(function (h) {
      if (h.section !== sec) { sec = h.section; tbody.appendChild(el('tr', { 'class': 'fsq-sec' }, [el('td', { colspan: '2', text: sec })])); }
      tbody.appendChild(el('tr', {}, [rich('th', '', h.question), el('td', { text: h.answer })]));
    });
    card.appendChild(el('table', { 'class': 'widefat striped' }, [tbody]));

    card.appendChild(el('h3', { text: 'Scope ofertă (generat din offer_mapping)' }));
    var ul = el('ul', { 'class': 'fsq-scope' }), any = false;
    Object.keys(scope).forEach(function (k) { any = true; ul.appendChild(el('li', {}, [el('strong', { text: (SCOPE_LABELS[k] || k) + ': ' }), rich('span', '', scope[k].join('; '))])); });
    card.appendChild(any ? ul : el('p', { text: 'Nicio regulă de scope nu se aplică acestor răspunsuri.' }));
    if (JSON.stringify(scope).match(CONFIRM_RE)) card.appendChild(el('p', { 'class': 'fsq-confirm-note', text: '⚠ Notă internă: elementele marcate [confirm] sunt afirmații tehnice neverificate. În textul pentru client marcajele sunt eliminate și înlocuite cu „(de confirmat)”.' }));

    function field(label, key, multi) {
      var i = multi ? el('textarea', { 'class': 'large-text', rows: '4' }) : el('input', { type: 'text', 'class': 'regular-text' });
      i.value = meta[key];
      i.addEventListener('input', function () { meta[key] = i.value; });
      return el('p', {}, [el('label', { text: label }), el('br'), i]);
    }
    card.appendChild(field('Persoană de contact (client)', 'client'));
    card.appendChild(field('Companie', 'company'));
    card.appendChild(field('Note întâlnire (intern)', 'notes', true));

    var status = el('span', { 'class': 'fsq-status' });
    var rows = history.map(function (h) { return { id: h.id, section: h.section, field: h.field, question: h.question, answer: h.answer, value: Array.isArray(h.value) ? h.value.join(',') : h.value }; });
    card.appendChild(el('p', { 'class': 'fsq-actions' }, [
      el('button', { type: 'button', 'class': 'button button-primary', text: 'Salvează fișa', on: { click: function (e) {
        var btn = e.target; btn.disabled = true; status.textContent = 'Se salvează…';
        var fd = new FormData();
        fd.append('action', 'fsq_save'); fd.append('nonce', cfg.nonce);
        fd.append('client', meta.client); fd.append('company', meta.company); fd.append('notes', meta.notes);
        fd.append('summary', internalText(scope)); fd.append('offer', clientText(scope)); fd.append('answers', JSON.stringify(rows));
        if (pre && pre.deal) fd.append('deal', pre.deal);
        fetch(cfg.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
          if (r && r.success) { status.textContent = 'Salvat. '; status.appendChild(el('a', { href: r.data.url, text: 'Deschide fișa' }));
            if (r.data.deal_url) { status.appendChild(document.createTextNode(' · ')); status.appendChild(el('a', { href: r.data.deal_url, text: 'Înapoi la card' })); } }
          else { status.textContent = 'Eroare la salvare.'; btn.disabled = false; }
        }).catch(function () { status.textContent = 'Eroare de rețea.'; btn.disabled = false; });
      } } }),
      el('button', { type: 'button', 'class': 'button', text: 'Copiază scope pentru client', on: { click: function () { copy(clientText(scope), status); } } }),
      el('button', { type: 'button', 'class': 'button', text: 'Export client .txt', on: { click: function () { download(slug() + '-oferta.txt', 'text/plain;charset=utf-8', clientText(scope)); } } }),
      el('button', { type: 'button', 'class': 'button', text: 'Copiază fișa internă', on: { click: function () { copy(internalText(scope), status); } } }),
      el('button', { type: 'button', 'class': 'button', text: 'Export intern .json', on: { click: function () {
        download(slug() + '-intern.json', 'application/json', JSON.stringify({ internal: true, tree_version: tree.version, date: new Date().toISOString(),
          client: meta.client, company: meta.company, notes: meta.notes, answers: rows, scope: scope, client_offer_text: clientText(scope) }, null, 2));
      } } }),
      status
    ]));
    app.appendChild(card);
    app.appendChild(navBar());
  }

  if (pre && pre.deal) {
    var banner = el('div', { 'class': 'notice notice-info fsq-linked' }, [el('p', { text: 'Legat de cardul: ' + (pre.title || ('#' + pre.deal)) + ' – fișa salvată se atașează automat cardului.' })]);
    app.parentNode.insertBefore(banner, app);
  }
  if (!tree.start_node || !nodes[tree.start_node]) { app.textContent = 'Arborele de întrebări nu are un nod de start valid.'; return; }
  render();
})();
