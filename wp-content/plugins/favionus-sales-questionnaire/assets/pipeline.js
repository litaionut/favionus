/* Favionus Sales – pipeline kanban. Admin-only, vanilla JS, no build step. All text via textContent (escaped). */
(function () {
  'use strict';
  var C = window.FSP || {}, board = document.getElementById('fsp-board');
  if (!board) return;
  var deals = C.deals || [], stages = C.stages || {}, outcomes = C.outcomes || {};
  var search = document.getElementById('fsp-search'), hideLost = document.getElementById('fsp-hide-lost');
  var eur = new Intl.NumberFormat('ro-RO', { maximumFractionDigits: 0 });

  function el(tag, attrs, kids) {
    var e = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      if (k === 'text') e.textContent = attrs[k];
      else if (k === 'on') Object.keys(attrs.on).forEach(function (ev) { e.addEventListener(ev, attrs.on[ev]); });
      else e.setAttribute(k, attrs[k]);
    });
    (kids || []).forEach(function (c) { if (c) e.appendChild(c); });
    return e;
  }
  function visible(d) {
    if (hideLost && hideLost.checked && d.outcome === 'pierdut') return false;
    var q = (search && search.value || '').trim().toLowerCase();
    if (!q) return true;
    return [d.title, d.company, d.contact, d.next_step].join(' ').toLowerCase().indexOf(q) >= 0;
  }
  function move(d, stage, outcome) {
    if (stage === 'inchis' && !outcome) {
      var a = window.prompt('Rezultat: scrie 1 pentru Câștigat sau 2 pentru Pierdut', '1');
      if (a === null) { render(); return; }
      outcome = a.trim() === '2' ? 'pierdut' : 'castigat';
    }
    if (stage !== 'inchis') outcome = '';
    var prev = { stage: d.stage, outcome: d.outcome };
    d.stage = stage; d.outcome = outcome; render();
    var fd = new FormData();
    fd.append('action', 'fsp_move'); fd.append('nonce', C.nonce); fd.append('id', d.id); fd.append('stage', stage); fd.append('outcome', outcome);
    fetch(C.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
      if (!r || !r.success) throw new Error();
    }).catch(function () { d.stage = prev.stage; d.outcome = prev.outcome; render(); window.alert('Nu s-a putut salva mutarea. Reîncarcă pagina.'); });
  }
  function card(d) {
    var sel = el('select', { 'class': 'fsp-stage-select', 'aria-label': 'Mută în etapa', on: { change: function () {
      var v = sel.value.split(':'); move(d, v[0], v[1] || '');
    } } });
    Object.keys(stages).forEach(function (s) {
      if (s === 'inchis') {
        ['castigat', 'pierdut'].forEach(function (o) { sel.appendChild(el('option', { value: s + ':' + o, text: stages[s] + ' – ' + outcomes[o] })); });
      } else sel.appendChild(el('option', { value: s, text: stages[s] }));
    });
    sel.value = d.stage === 'inchis' ? 'inchis:' + (d.outcome || 'castigat') : d.stage;
    var c = el('div', { 'class': 'fsp-card' + (d.outcome ? ' is-' + d.outcome : ''), draggable: 'true', 'data-id': d.id }, [
      el('a', { 'class': 'fsp-card-title', href: C.edit + d.id, text: d.title }),
      d.outcome ? el('span', { 'class': 'fsp-badge', text: outcomes[d.outcome] }) : null,
      d.company ? el('div', { 'class': 'fsp-line', text: '🏢 ' + d.company }) : null,
      d.contact ? el('div', { 'class': 'fsp-line', text: '👤 ' + d.contact }) : null,
      d.value ? el('div', { 'class': 'fsp-line fsp-value', text: eur.format(d.value) + ' EUR' }) : null,
      d.next_step || d.next_date ? el('div', { 'class': 'fsp-line fsp-next' + (d.next_date && d.next_date < new Date().toISOString().slice(0, 10) ? ' is-overdue' : ''),
        text: '→ ' + (d.next_step || '') + (d.next_date ? ' (' + d.next_date + ')' : '') }) : null,
      sel
    ]);
    c.addEventListener('dragstart', function (e) { e.dataTransfer.setData('text/plain', String(d.id)); c.classList.add('is-dragging'); });
    c.addEventListener('dragend', function () { c.classList.remove('is-dragging'); });
    c.addEventListener('click', function (e) { if (e.target.tagName !== 'SELECT' && e.target.tagName !== 'OPTION' && e.target.tagName !== 'A') window.location.href = C.edit + d.id; });
    return c;
  }
  function render() {
    board.innerHTML = '';
    Object.keys(stages).forEach(function (s) {
      var list = deals.filter(function (d) { return d.stage === s && visible(d); });
      var total = list.reduce(function (t, d) { return t + (d.outcome === 'pierdut' ? 0 : (+d.value || 0)); }, 0);
      var body = el('div', { 'class': 'fsp-col-body' });
      list.forEach(function (d) { body.appendChild(card(d)); });
      var col = el('div', { 'class': 'fsp-col', 'data-stage': s }, [
        el('div', { 'class': 'fsp-col-head' }, [el('strong', { text: stages[s] }), el('span', { text: list.length + ' · ' + eur.format(total) + ' EUR' })]),
        body,
        el('a', { 'class': 'fsp-add', href: C.edit + 'new', text: '+ Deal nou' })
      ]);
      col.addEventListener('dragover', function (e) { e.preventDefault(); col.classList.add('is-over'); });
      col.addEventListener('dragleave', function () { col.classList.remove('is-over'); });
      col.addEventListener('drop', function (e) {
        e.preventDefault(); col.classList.remove('is-over');
        var id = +e.dataTransfer.getData('text/plain'), d = deals.filter(function (x) { return x.id === id; })[0];
        if (d && d.stage !== s) move(d, s, '');
      });
      board.appendChild(col);
    });
  }
  if (search) search.addEventListener('input', render);
  if (hideLost) hideLost.addEventListener('change', render);
  render();
})();
