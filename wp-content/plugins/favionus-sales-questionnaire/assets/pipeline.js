/* Favionus Sales – pipeline kanban v2. Admin-only, vanilla JS, no build step. All text via textContent (escaped). */
(function () {
  'use strict';
  var C = window.FSP || {}, board = document.getElementById('fsp-board');
  if (!board) return;
  var deals = C.deals || [], stages = C.stages || {}, kinds = C.kinds || {}, types = C.types || {}, reasons = C.reasons || {};
  var eur = new Intl.NumberFormat('ro-RO', { maximumFractionDigits: 0 });
  var F = { q: '', stage: '', country: '', type: '', source: '', overdue: false };

  function el(tag, attrs, kids) {
    var e = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      if (k === 'text') e.textContent = attrs[k];
      else if (k === 'on') Object.keys(attrs.on).forEach(function (ev) { e.addEventListener(ev, attrs.on[ev]); });
      else if (attrs[k] != null) e.setAttribute(k, attrs[k]);
    });
    (kids || []).forEach(function (c) { if (c) e.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
    return e;
  }
  function post(action, data) {
    var fd = new FormData();
    fd.append('action', action); fd.append('nonce', C.nonce);
    Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    return fetch(C.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
      if (!r || !r.success) throw new Error((r && r.data) || 'error');
      return r.data;
    });
  }
  function replace(card) { for (var i = 0; i < deals.length; i++) if (deals[i].id === card.id) deals[i] = card; }

  /* ---- filters ---- */
  function uniq(key) { var s = {}; deals.forEach(function (d) { if (d[key]) s[d[key]] = 1; }); return Object.keys(s).sort(); }
  function select(label, key, opts) {
    var s = el('select', { 'aria-label': label, on: { change: function () { F[key] = s.value; render(); } } }, [el('option', { value: '', text: label + ': toate' })]);
    Object.keys(opts).forEach(function (v) { if (v) s.appendChild(el('option', { value: v, text: opts[v] })); });
    s.value = F[key];
    return s;
  }
  function toObj(list) { var o = {}; list.forEach(function (v) { o[v] = v; }); return o; }
  function renderFilters() {
    var box = document.getElementById('fsp-filters'); if (!box) return;
    box.innerHTML = '';
    var q = el('input', { type: 'search', 'class': 'regular-text', placeholder: 'Caută nume, companie, proiect, email, note…', on: { input: function () { F.q = q.value; render(); } } });
    q.value = F.q;
    var od = el('input', { type: 'checkbox', on: { change: function () { F.overdue = od.checked; render(); } } }); od.checked = F.overdue;
    box.appendChild(q);
    box.appendChild(select('Coloană', 'stage', stages));
    box.appendChild(select('Țară', 'country', toObj(uniq('country'))));
    box.appendChild(select('Tip', 'type', types));
    box.appendChild(select('Sursă', 'source', toObj(uniq('source'))));
    box.appendChild(el('label', { 'class': 'fsp-od-filter' }, [od, ' Doar follow-up întârziat']));
    box.appendChild(el('button', { type: 'button', 'class': 'button-link', text: 'Resetează', on: { click: function () { F = { q: '', stage: '', country: '', type: '', source: '', overdue: false }; renderFilters(); render(); } } }));
  }
  function visible(d) {
    if (F.stage && d.stage !== F.stage) return false;
    if (F.country && d.country !== F.country) return false;
    if (F.type && d.project_type !== F.type) return false;
    if (F.source && d.source !== F.source) return false;
    if (F.overdue && !d.overdue) return false;
    var q = F.q.trim().toLowerCase();
    if (q && [d.title, d.contact_name, d.company_name, d.project_name, d.email, d.location, d.next_step, d.notes].join(' ').toLowerCase().indexOf(q) < 0) return false;
    return true;
  }
  var exp = document.getElementById('fsp-export');
  if (exp) exp.addEventListener('click', function (e) {
    e.preventDefault();
    var u = C.export;
    ['q', 'stage', 'country', 'type', 'source'].forEach(function (k) { if (F[k]) u += '&' + k + '=' + encodeURIComponent(F[k]); });
    if (F.overdue) u += '&overdue=1';
    window.location.href = u;
  });

  /* ---- modal ---- */
  function modal(title, body, okText, onOk) {
    var root = document.getElementById('fsp-modal-root') || document.body;
    var close = function () { wrap.remove(); document.removeEventListener('keydown', esc); };
    var esc = function (e) { if (e.key === 'Escape') close(); };
    var err = el('p', { 'class': 'fsp-modal-err' });
    var ok = el('button', { type: 'button', 'class': 'button button-primary', text: okText, on: { click: function () {
      ok.disabled = true; err.textContent = '';
      Promise.resolve(onOk()).then(close, function (x) { ok.disabled = false; err.textContent = 'Nu s-a putut salva: ' + (x && x.message || 'eroare'); });
    } } });
    var wrap = el('div', { 'class': 'fsp-modal-backdrop', on: { click: function (e) { if (e.target === wrap) close(); } } }, [
      el('div', { 'class': 'fsp-modal', role: 'dialog', 'aria-modal': 'true', 'aria-label': title }, [
        el('h2', { text: title }), body, err,
        el('p', { 'class': 'fsp-modal-actions' }, [el('button', { type: 'button', 'class': 'button', text: 'Anulează', on: { click: close } }), ok])
      ])
    ]);
    root.appendChild(wrap);
    document.addEventListener('keydown', esc);
    var f = wrap.querySelector('input,select,textarea'); if (f) f.focus();
  }
  function field(label, input) { return el('p', {}, [el('label', { text: label }), el('br'), input]); }

  function move(d, stage) {
    if (d.stage === stage) return;
    var k = kinds[stage];
    if (k === 'won' || k === 'lost') {
      var note = el('textarea', { rows: '3', 'class': 'large-text' });
      var body = el('div'), extra = {};
      if (k === 'won') {
        var val = el('input', { type: 'number', step: 'any', 'class': 'regular-text', value: d.value_eur || '' });
        body.appendChild(el('p', { text: 'Marchezi „' + d.title + '” ca vândut.' }));
        body.appendChild(field('Valoare finală (EUR)', val));
        extra.get = function () { return { value: val.value }; };
      } else {
        var rs = el('select');
        Object.keys(reasons).forEach(function (r) { rs.appendChild(el('option', { value: r, text: reasons[r] })); });
        body.appendChild(el('p', { text: 'Marchezi „' + d.title + '” ca pierdut.' }));
        body.appendChild(field('Motiv', rs));
        extra.get = function () { return { reason: rs.value }; };
      }
      body.appendChild(field('Notă (opțional)', note));
      modal(stages[stage], body, 'Confirmă', function () {
        var data = extra.get(); data.id = d.id; data.stage = stage; data.note = note.value;
        return post('fsp_move', data).then(function (c) { replace(c); render(); });
      });
      render(); // reset dropdowns / dragged card while modal is open
      return;
    }
    var prev = d.stage; d.stage = stage; render();
    post('fsp_move', { id: d.id, stage: stage }).then(function (c) { replace(c); render(); })
      .catch(function () { d.stage = prev; render(); window.alert('Nu s-a putut salva mutarea. Reîncarcă pagina.'); });
  }
  function addNote(d) {
    var t = el('textarea', { rows: '4', 'class': 'large-text', placeholder: 'Apel, email, discuție…' });
    var tl = el('ol', { 'class': 'fsp-timeline fsp-timeline-compact' }, [el('li', { text: 'Se încarcă istoricul…' })]);
    var body = el('div', {}, [field('Notă nouă', t), el('h3', { text: 'Timeline' }), tl]);
    post('fsp_timeline', { id: d.id }).then(function (items) {
      tl.innerHTML = '';
      if (!items.length) tl.appendChild(el('li', { text: '—' }));
      items.slice(0, 15).forEach(function (e) {
        var what = e.type === 'stage' || e.type === 'migrate' ? ((stages[e.from] || e.from || '—') + ' → ' + (stages[e.to] || e.to)) : '';
        tl.appendChild(el('li', { 'class': 'fsp-tl-' + e.type }, [el('span', { 'class': 'fsp-tl-meta', text: e.t + ' · ' + e.user }),
          what ? el('div', { text: what }) : null, e.text ? el('div', { text: e.text }) : null]));
      });
    }).catch(function () { tl.innerHTML = ''; });
    modal('Notă – ' + d.title, body, 'Adaugă nota', function () {
      if (!t.value.trim()) return Promise.reject(new Error('nota e goală'));
      return post('fsp_note', { id: d.id, text: t.value }).then(function (c) { replace(c); render(); });
    });
  }

  /* ---- cards ---- */
  function card(d) {
    var sel = el('select', { 'class': 'fsp-stage-select', 'aria-label': 'Mută în coloana', on: { change: function () { move(d, sel.value); } } });
    Object.keys(stages).forEach(function (s) { sel.appendChild(el('option', { value: s, text: stages[s] })); });
    sel.value = d.stage;
    var proj = [d.project_name, [d.location, d.country].filter(Boolean).join(', '), d.mw ? d.mw + ' MW' : '', types[d.project_type] && d.project_type ? types[d.project_type] : ''].filter(Boolean).join(' · ');
    var recs = null;
    if (d.records && d.records.length) {
      recs = el('details', { 'class': 'fsp-recs' }, [el('summary', { text: '📋 ' + d.records.length + (d.records.length === 1 ? ' fișă chestionar' : ' fișe chestionar') })]);
      d.records.forEach(function (r) {
        recs.appendChild(el('a', { href: r.url, text: r.title }));
        if (r.offer) recs.appendChild(el('pre', { 'class': 'fsp-offer', text: r.offer.split('\n').filter(function (l) { return /^- /.test(l); }).join('\n') || r.offer }));
      });
    }
    var k = kinds[d.stage];
    var c = el('div', { 'class': 'fsp-card' + (k === 'won' ? ' is-won' : k === 'lost' ? ' is-lost' : '') + (d.overdue ? ' is-overdue' : ''), draggable: 'true', 'data-id': d.id }, [
      el('a', { 'class': 'fsp-card-title', href: C.edit + d.id, text: d.title }),
      d.role || (d.company_name && d.company_name !== d.title) ? el('div', { 'class': 'fsp-line fsp-sub', text: [d.role, d.company_name !== d.title ? d.company_name : ''].filter(Boolean).join(' · ') }) : null,
      d.email || d.phone ? el('div', { 'class': 'fsp-line fsp-contact' }, [
        d.email ? el('a', { href: 'mailto:' + d.email, text: d.email }) : null, d.email && d.phone ? ' · ' : null,
        d.phone ? el('a', { href: 'tel:' + d.phone.replace(/[^0-9+]/g, ''), text: d.phone }) : null]) : null,
      proj ? el('div', { 'class': 'fsp-line fsp-proj', text: proj }) : null,
      d.value_eur ? el('div', { 'class': 'fsp-line fsp-value', text: eur.format(d.value_eur) + ' EUR' }) : null,
      d.next_step || d.next_date ? el('div', { 'class': 'fsp-line fsp-next' + (d.overdue ? ' is-overdue' : ''),
        text: (d.overdue ? '⚠ ' : '→ ') + (d.next_step || 'Follow-up') + (d.next_date ? ' · ' + d.next_date : '') }) : null,
      d.source ? el('span', { 'class': 'fsp-badge', text: d.source }) : null,
      recs,
      el('div', { 'class': 'fsp-card-actions' }, [
        el('button', { type: 'button', 'class': 'button button-small', text: '+ Notă', on: { click: function () { addNote(d); } } }),
        el('a', { 'class': 'button button-small', href: C.quest + d.id, text: 'Pornește chestionarul' })
      ]),
      sel
    ]);
    c.addEventListener('dragstart', function (e) { e.dataTransfer.setData('text/plain', String(d.id)); c.classList.add('is-dragging'); });
    c.addEventListener('dragend', function () { c.classList.remove('is-dragging'); });
    return c;
  }
  function render() {
    board.innerHTML = '';
    var keys = Object.keys(stages);
    board.style.gridTemplateColumns = 'repeat(' + keys.length + ', minmax(230px, 1fr))';
    keys.forEach(function (s) {
      var list = deals.filter(function (d) { return d.stage === s && visible(d); });
      var total = list.reduce(function (t, d) { return t + (+d.value_eur || 0); }, 0);
      var od = list.filter(function (d) { return d.overdue; }).length;
      var body = el('div', { 'class': 'fsp-col-body' });
      list.forEach(function (d) { body.appendChild(card(d)); });
      if (!list.length) body.appendChild(el('p', { 'class': 'fsp-empty', text: 'Niciun card' }));
      var col = el('div', { 'class': 'fsp-col fsp-kind-' + (kinds[s] || 'open'), 'data-stage': s }, [
        el('div', { 'class': 'fsp-col-head' }, [el('strong', { text: stages[s] }),
          el('span', { text: list.length + ' · ' + eur.format(total) + ' EUR' + (od ? ' · ⚠ ' + od : '') })]),
        body,
        el('a', { 'class': 'fsp-add', href: C.edit + 'new', text: '+ Card nou' })
      ]);
      col.addEventListener('dragover', function (e) { e.preventDefault(); col.classList.add('is-over'); });
      col.addEventListener('dragleave', function () { col.classList.remove('is-over'); });
      col.addEventListener('drop', function (e) {
        e.preventDefault(); col.classList.remove('is-over');
        var id = +e.dataTransfer.getData('text/plain'), d = deals.filter(function (x) { return x.id === id; })[0];
        if (d) move(d, s);
      });
      board.appendChild(col);
    });
  }
  renderFilters();
  render();
})();
