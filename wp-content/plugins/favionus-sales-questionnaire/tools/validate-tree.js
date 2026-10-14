#!/usr/bin/env node
/* Validate a Favionus sales question tree.  Usage: node validate-tree.js path/to/question-tree.json
 * Exit code 1 on errors (bad structure, duplicate/dangling ids, missing start); warnings for cycles,
 * unreachable nodes, paths that never reach an end node. */
'use strict';
const fs = require('fs');
const file = process.argv[2] || require('path').join(__dirname, '..', 'question-tree.json');
const errors = [], warnings = [];
let tree;
try { tree = JSON.parse(fs.readFileSync(file, 'utf8')); } catch (e) { console.error('ERROR: cannot parse ' + file + ': ' + e.message); process.exit(1); }
const TYPES = ['choice', 'text', 'number', 'end'];
const nodes = {};
if (!Array.isArray(tree.nodes)) errors.push('"nodes" must be an array');
(tree.nodes || []).forEach((n, i) => {
  const where = `nodes[${i}]` + (n && n.id ? ` (${n.id})` : '');
  if (!n || typeof n.id !== 'string' || !/^[a-z0-9_\-]+$/.test(n.id)) return errors.push(`${where}: id must be a lowercase string [a-z0-9_-]`);
  if (nodes[n.id]) errors.push(`${where}: duplicate id`);
  nodes[n.id] = n;
  if (!TYPES.includes(n.type)) errors.push(`${where}: type must be one of ${TYPES.join('|')}`);
  if (!n.text_ro && !n.text_en && !n.text && n.type !== 'end') errors.push(`${where}: needs text_ro or text_en`);
  if (n.type === 'choice') {
    if (!Array.isArray(n.options) || !n.options.length) errors.push(`${where}: choice needs non-empty options`);
    (n.options || []).forEach((o, j) => {
      if (!o.label_ro && !o.label_en && !o.label) errors.push(`${where}.options[${j}]: needs label_ro or label_en`);
      if (!o.next && !n.next) errors.push(`${where}.options[${j}]: no "next" and node has no default "next"`);
    });
  } else if (n.type === 'text' || n.type === 'number') {
    if (!n.next) errors.push(`${where}: ${n.type} needs "next"`);
  } else if (n.type === 'end' && (n.next || n.options)) warnings.push(`${where}: end node has next/options (ignored)`);
});
const edges = id => { const n = nodes[id]; if (!n || n.type === 'end') return [];
  return n.type === 'choice' ? [...new Set((n.options || []).map(o => o.next || n.next).filter(Boolean))] : (n.next ? [n.next] : []); };
Object.keys(nodes).forEach(id => edges(id).forEach(t => { if (!nodes[t]) errors.push(`${id}: next -> "${t}" does not exist (dangling)`); }));
if (!tree.start) errors.push('missing "start"'); else if (!nodes[tree.start]) errors.push(`start "${tree.start}" does not exist`);
if (!Object.values(nodes).some(n => n.type === 'end')) errors.push('no end node');
if (nodes[tree.start]) {
  const seen = new Set(), stack = [tree.start];
  while (stack.length) { const id = stack.pop(); if (seen.has(id) || !nodes[id]) continue; seen.add(id); edges(id).forEach(t => stack.push(t)); }
  Object.keys(nodes).filter(id => !seen.has(id)).forEach(id => warnings.push(`${id}: unreachable from start`));
  // cycles (DFS colouring)
  const col = {}; const cyc = new Set();
  const dfs = (id, path) => { col[id] = 1; for (const t of edges(id)) { if (!nodes[t]) continue;
      if (col[t] === 1) cyc.add([...path.slice(path.indexOf(t)), t].join(' -> ')); else if (!col[t]) dfs(t, [...path, t]); } col[id] = 2; };
  dfs(tree.start, [tree.start]);
  cyc.forEach(c => warnings.push('cycle: ' + c));
  // nodes that can never reach an end
  const canEnd = new Set(Object.keys(nodes).filter(id => nodes[id].type === 'end'));
  let changed = true; while (changed) { changed = false; for (const id of Object.keys(nodes)) if (!canEnd.has(id) && edges(id).some(t => canEnd.has(t))) { canEnd.add(id); changed = true; } }
  [...seen].filter(id => !canEnd.has(id)).forEach(id => warnings.push(`${id}: no path to an end node`));
}
if (/EXEMPLU|EXAMPLE/.test(JSON.stringify(tree))) warnings.push('tree is marked EXAMPLE – replace with the real Favionus Sales tree before use');
console.log(`File: ${file}\nNodes: ${Object.keys(nodes).length}, start: ${tree.start}`);
warnings.forEach(w => console.log('WARN:  ' + w));
errors.forEach(e => console.log('ERROR: ' + e));
console.log(errors.length ? `FAILED (${errors.length} errors, ${warnings.length} warnings)` : `OK (${warnings.length} warnings)`);
process.exit(errors.length ? 1 : 0);
