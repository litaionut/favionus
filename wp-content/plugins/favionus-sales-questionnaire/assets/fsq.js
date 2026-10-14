/* Favionus Sales Questionnaire – admin-only, vanilla JS, no build step. */
(function () {
  'use strict';
  var cfg = window.FSQ || {};
  var tree = cfg.tree || {};
  var nodes = {};
  (tree.nodes || []).forEach(function (n) { nodes[n.id] = n; });
  var app = document.getElementById('fsq-app');
  if (!app) return;

  var history = [];   // stack of {id, answer, value, question}
  var current = tree.start;
  var meta = { client: '', company: '', notes: '' };

  function txt(o, base) {
    if (!o) return '';
    return o[base + '_ro'] || o[base + '_en'] || o[base] || '';
  }
  function el(tag, attrs, children) {
    var e = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      if (k === 'text') e.textContent = attrs[k];
      else if (k === 'on') Object.keys(attrs.on).forEach(function (ev) { e.addEventListener(ev, attrs.on[ev]); });
      else e.setAttribute(k, attrs[k]);
    });
    (children || []).forEach(function (c) { if (c) e.appendChild(c); });
    return e;
  }
  function depthEstimate() {
    // longest remaining path from current (bounded, cycle-safe)
    var memo = {};
    function d(id, seen) {
      var n = nodes[id];
      if (!n || n.type === 'end' || seen[id]) return 0;
      if (memo[id] != null) return memo[id];
      seen[id] = 1;
      var nexts = n.type === 'choice' ? (n.options || []).map(function (o) { return o.next || n.next; }) : [n.next];
      var m = 0;
      nexts.forEach(function (x) { if (x) m = Math.max(m, d(x, seen)); });
      delete seen[id];
      return (memo[id] = m + 1);
    }
    return d(current, {});
  }
  function go(next, answer, value) {
    var n = nodes[current];
    history.push({ id: current, question: txt(n, 'text'), answer: answer, value: value });
    current = next;
    render();
  }
  function back() {
    if (!history.length) return;
    current = history.pop().id;
    render();
  }

  function render() {
    app.innerHTML = '';
    var n = nodes[current];
    if (!n) {
      app.appendChild(el('div', { 'class': 'notice notice-error', text: 'Întrebare inexistentă: ' + current }));
      app.appendChild(navBar());
      return;
    }
    if (n.type === 'end') return renderSummary(n);

    var done = history.length, left = depthEstimate();
    var pct = Math.round(done / Math.max(1, done + left) * 100);
    app.appendChild(el('div', { 'class': 'fsq-progress' }, [
      el('div', { 'class': 'fsq-bar', style: 'width:' + pct + '%' }),
      el('span', { text: 'Întrebarea ' + (done + 1) + ' · ~' + Math.max(0, left - 1) + ' rămase' })
    ]));
    var card = el('div', { 'class': 'fsq-card' }, [el('h2', { text: txt(n, 'text') })]);
    var help = txt(n, 'help');
    if (help) card.appendChild(el('p', { 'class': 'description', text: help }));

    if (n.type === 'choice') {
      var box = el('div', { 'class': 'fsq-options' });
      (n.options || []).forEach(function (o) {
        box.appendChild(el('button', { type: 'button', 'class': 'button button-hero fsq-opt', text: txt(o, 'label'),
          on: { click: function () { go(o.next || n.next, txt(o, 'label'), o.value != null ? String(o.value) : ''); } } }));
      });
      card.appendChild(box);
    } else {
      var input = n.type === 'number'
        ? el('input', { type: 'number', 'class': 'regular-text', step: 'any' })
        : el('textarea', { 'class': 'large-text', rows: '3' });
      if (n.type === 'number') {
        if (n.min != null) input.setAttribute('min', n.min);
        if (n.max != null) input.setAttribute('max', n.max);
      }
      var ph = txt(n, 'placeholder'); if (ph) input.setAttribute('placeholder', ph);
      var unit = txt(n, 'unit');
      var submit = function () {
        var v = input.value.trim();
        if (n.required && !v) { input.focus(); return; }
        go(n.next, v ? (unit ? v + ' ' + unit : v) : '—', v);
      };
      input.addEventListener('keydown', function (e) { if (e.key === 'Enter' && (n.type === 'number' || e.ctrlKey)) { e.preventDefault(); submit(); } });
      card.appendChild(el('p', {}, [input, unit ? el('span', { 'class': 'fsq-unit', text: ' ' + unit }) : null]));
      card.appendChild(el('button', { type: 'button', 'class': 'button button-primary', text: 'Continuă →', on: { click: submit } }));
      setTimeout(function () { input.focus(); }, 0);
    }
    app.appendChild(card);
    app.appendChild(navBar());
  }

  function navBar() {
    return el('p', { 'class': 'fsq-nav' }, [
      el('button', { type: 'button', 'class': 'button', text: '← Înapoi', on: { click: back } }),
      el('button', { type: 'button', 'class': 'button-link fsq-restart', text: 'Reia de la început',
        on: { click: function () { if (confirm('Reiei chestionarul? Răspunsurile se pierd.')) { history = []; current = tree.start; render(); } } } })
    ]);
  }

  function summaryText(endNode) {
    var lines = ['FAVIONUS – Fișă întâlnire vânzări', 'Data: ' + new Date().toLocaleString('ro-RO'),
      'Client: ' + (meta.client || '—'), 'Companie: ' + (meta.company || '—'), ''];
    history.forEach(function (h, i) { lines.push((i + 1) + '. ' + h.question); lines.push('   → ' + h.answer); });
    var out = txt(endNode, 'text'); if (out) { lines.push(''); lines.push('Concluzie: ' + out); }
    if (meta.notes) { lines.push(''); lines.push('Note întâlnire:'); lines.push(meta.notes); }
    return lines.join('\n');
  }
  function download(name, mime, content) {
    var a = el('a', { href: URL.createObjectURL(new Blob([content], { type: mime })), download: name });
    document.body.appendChild(a); a.click();
    setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
  }
  function slug() { return ((meta.company || 'fisa') + '-' + new Date().toISOString().slice(0, 10)).toLowerCase().replace(/[^a-z0-9-]+/g, '-'); }

  function renderSummary(endNode) {
    app.appendChild(el('div', { 'class': 'fsq-progress' }, [el('div', { 'class': 'fsq-bar', style: 'width:100%' }), el('span', { text: 'Gata' })]));
    var card = el('div', { 'class': 'fsq-card' }, [el('h2', { text: 'Rezumat' })]);
    var outcome = txt(endNode, 'text'); if (outcome) card.appendChild(el('p', { 'class': 'fsq-outcome', text: outcome }));
    var tbody = el('tbody');
    history.forEach(function (h) { tbody.appendChild(el('tr', {}, [el('th', { text: h.question }), el('td', { text: h.answer })])); });
    card.appendChild(el('table', { 'class': 'widefat striped' }, [tbody]));

    function field(label, key, multi) {
      var i = multi ? el('textarea', { 'class': 'large-text', rows: '4' }) : el('input', { type: 'text', 'class': 'regular-text' });
      i.value = meta[key];
      i.addEventListener('input', function () { meta[key] = i.value; });
      return el('p', {}, [el('label', { text: label }), el('br'), i]);
    }
    card.appendChild(field('Persoană de contact (client)', 'client'));
    card.appendChild(field('Companie', 'company'));
    card.appendChild(field('Note întâlnire', 'notes', true));

    var status = el('span', { 'class': 'fsq-status' });
    var answers = history.map(function (h) { return { id: h.id, question: h.question, answer: h.answer, value: h.value }; });
    card.appendChild(el('p', { 'class': 'fsq-actions' }, [
      el('button', { type: 'button', 'class': 'button button-primary', text: 'Salvează fișa', on: { click: function (e) {
        var btn = e.target; btn.disabled = true; status.textContent = 'Se salvează…';
        var fd = new FormData();
        fd.append('action', 'fsq_save'); fd.append('nonce', cfg.nonce);
        fd.append('client', meta.client); fd.append('company', meta.company); fd.append('notes', meta.notes);
        fd.append('summary', summaryText(endNode)); fd.append('answers', JSON.stringify(answers));
        fetch(cfg.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
          if (r && r.success) { status.textContent = 'Salvat. '; status.appendChild(el('a', { href: r.data.url, text: 'Deschide fișa' })); }
          else { status.textContent = 'Eroare la salvare.'; btn.disabled = false; }
        }).catch(function () { status.textContent = 'Eroare de rețea.'; btn.disabled = false; });
      } } }),
      el('button', { type: 'button', 'class': 'button', text: 'Copiază pentru ofertă', on: { click: function () {
        var t = summaryText(endNode);
        (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(
          function () { status.textContent = 'Copiat în clipboard.'; },
          function () { var ta = el('textarea'); ta.value = t; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); status.textContent = 'Copiat.'; });
      } } }),
      el('button', { type: 'button', 'class': 'button', text: 'Export .txt', on: { click: function () { download(slug() + '.txt', 'text/plain;charset=utf-8', summaryText(endNode)); } } }),
      el('button', { type: 'button', 'class': 'button', text: 'Export .json', on: { click: function () {
        download(slug() + '.json', 'application/json', JSON.stringify({ date: new Date().toISOString(), client: meta.client, company: meta.company, notes: meta.notes, outcome: outcome, answers: answers }, null, 2));
      } } }),
      status
    ]));
    app.appendChild(card);
    app.appendChild(navBar());
  }

  if (!tree.start || !nodes[tree.start]) { app.textContent = 'Arborele de întrebări nu are un nod de start valid.'; return; }
  render();
})();
