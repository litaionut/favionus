#!/usr/bin/env node
/* Validate a Favionus sales qualification tree (schema of /workspace/sales-form/qualification_tree.json).
 * Usage: node tools/validate-tree.js [path]   (default: ../question-tree.json)
 * Errors (exit 1): bad structure/type, duplicate ids, dangling next, missing start/summary, multi/single without options,
 *   input without next, node that cannot reach a summary, unreachable node, offer_mapping on unknown field/value.
 * Warnings: cycles, [confirm] markers (unverified technical claims). */
'use strict';
const fs = require('fs'), path = require('path');
const file = process.argv[2] || path.join(__dirname, '..', 'question-tree.json');
const E = [], W = [];
let T;
try { T = JSON.parse(fs.readFileSync(file, 'utf8')); } catch (e) { console.error('ERROR: cannot parse ' + file + ': ' + e.message); process.exit(1); }
const TYPES = ['single', 'multi', 'input', 'summary'];
const N = {};
if (!Array.isArray(T.nodes)) E.push('"nodes" must be an array');
(T.nodes || []).forEach((n, i) => {
  const w = `nodes[${i}]` + (n && n.id ? ` (${n.id})` : '');
  if (!n || typeof n.id !== 'string' || !n.id) return E.push(`${w}: missing id`);
  if (N[n.id]) E.push(`${w}: duplicate id`);
  N[n.id] = n;
  if (!TYPES.includes(n.type)) E.push(`${w}: type must be ${TYPES.join('|')}`);
  if (!n.question) E.push(`${w}: missing question`);
  if (!n.field) W.push(`${w}: no field (answers stored under node id)`);
  if ((n.type === 'single' || n.type === 'multi') && !(n.options || []).length) E.push(`${w}: ${n.type} needs options`);
  if (n.type === 'single') (n.options || []).forEach((o, j) => { if (!o.next && !n.next) E.push(`${w}.options[${j}]: no next`); });
  if (n.type === 'multi') { const s = new Set((n.options || []).map(o => o.next || n.next)); if (s.size > 1 && !n.next) W.push(`${w}: multi options point to different next ids; UI follows node.next or the first ticked option`); }
  if (n.type === 'input' && !n.next) E.push(`${w}: input needs next`);
});
const nx = id => { const n = N[id]; if (!n || n.type === 'summary') return [];
  return [...new Set([...(n.options || []).map(o => o.next), n.next].filter(Boolean))]; };
Object.keys(N).forEach(id => nx(id).forEach(t => { if (!N[t]) E.push(`${id} -> "${t}" missing (dangling)`); }));
const start = T.start_node;
if (!start || !N[start]) E.push(`start_node "${start}" missing`);
if (!Object.values(N).some(n => n.type === 'summary')) E.push('no summary node');
if (N[start]) {
  const seen = new Set(), st = [start];
  while (st.length) { const x = st.pop(); if (seen.has(x) || !N[x]) continue; seen.add(x); st.push(...nx(x)); }
  Object.keys(N).filter(x => !seen.has(x)).forEach(x => E.push(`${x}: unreachable from start`));
  const col = {}, cyc = new Set();
  const dfs = (id, p) => { col[id] = 1; for (const t of nx(id)) { if (!N[t]) continue; if (col[t] === 1) cyc.add([...p.slice(p.indexOf(t)), t].join(' -> ')); else if (!col[t]) dfs(t, [...p, t]); } col[id] = 2; };
  dfs(start, [start]); cyc.forEach(c => W.push('cycle: ' + c));
}
const ok = new Set(Object.keys(N).filter(x => N[x].type === 'summary')); let ch = true;
while (ch) { ch = false; for (const x of Object.keys(N)) if (!ok.has(x) && nx(x).some(y => ok.has(y))) { ok.add(x); ch = true; } }
Object.keys(N).filter(x => !ok.has(x)).forEach(x => E.push(`${x}: cannot reach summary`));
const fields = {}; Object.values(N).forEach(n => { fields[n.field || n.id] = new Set((n.options || []).map(o => String(o.value))); });
(T.offer_mapping || []).forEach((r, i) => {
  if (!r.if || !r.scope) return E.push(`offer_mapping[${i}]: needs if + scope`);
  Object.entries(r.if).forEach(([f, v]) => {
    if (!fields[f]) return E.push(`offer_mapping[${i}]: unknown field "${f}"`);
    if (v === '*' || !fields[f].size) return;
    (Array.isArray(v) ? v : [v]).forEach(x => { if (!fields[f].has(String(x))) E.push(`offer_mapping[${i}]: field "${f}" has no option value "${x}"`); });
  });
});
const confirms = (JSON.stringify(T).match(/\[confirm[^\]]*\]/gi) || []).length;
if (confirms) W.push(`${confirms} [confirm] marker(s): unverified technical claims (highlighted in UI, stripped/flagged in client export)`);
console.log(`File: ${file}\nNodes: ${Object.keys(N).length}, start: ${start}, sections: ${[...new Set(Object.values(N).map(n => n.section))].length}, mapping rules: ${(T.offer_mapping || []).length}`);
W.forEach(w => console.log('WARN:  ' + w)); E.forEach(e => console.log('ERROR: ' + e));
console.log(E.length ? `FAILED (${E.length} errors, ${W.length} warnings)` : `OK (${W.length} warnings)`);
process.exit(E.length ? 1 : 0);
