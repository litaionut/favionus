# Favionus Sales Questionnaire (admin-only)

wp-admin → **Chestionar vânzări** (run) and **Fișe salvate** (saved records: view/delete). Requires `manage_options`.
No front-end output, shortcode or REST route. Save = `admin-ajax` `wp_ajax_fsq_save` (no `nopriv`) with capability + nonce;
delete = `admin-post` with capability + nonce. Records: private CPT `favionus_sales_rec` (not public/queryable/searchable,
no REST, no rewrite, excluded from WP + Yoast sitemaps). Admin screens send `X-Robots-Tag: noindex` + robots meta.

## Tree – `question-tree.json` (copy of Favionus Sales `qualification_tree.json`)
```
{ version, company, start_node,
  nodes: [ { id, section, question, help_text,
             type: "single" | "multi" | "input" | "summary",
             field,                         // answer key used by offer_mapping
             options: [ { label, value, next } ],
             next } ],                      // input: required; multi: optional (else first ticked option's next)
  offer_mapping: [ { if: { field: "value" | ["v1","v2"] | "*" }, scope: { key: "text" | "from field X" } } ] }
```
offer_mapping: all conditions must match (multi answers match if any ticked value matches). Rules apply in order,
values per scope key accumulate, but a more specific earlier rule (superset of conditions) blocks a more general one
for the same key. `"from field X"` = labels answered for X.

`[confirm…]` markers = unverified technical claims: highlighted in the UI with an internal note; the client copy/export
strips them and appends “(de confirmat)”. Internal copy/JSON keeps them.

Validate before replacing the tree: `node tools/validate-tree.js path/to/tree.json`. Replace by uploading a new
`question-tree.json` over the plugin's file – no code change needed.

# Pipeline CRM (kanban) – since 0.2.0
Menu **Favionus Sales → Pipeline / Deal-uri / Companii / Contacte**. Same rules: `manage_options` + nonces, no public output.
Data: private CPTs `fsp_company` (name, vat, country, website, notes), `fsp_contact` (name, role, email, phone, company, notes),
`fsp_deal` (title, company, contacts[], stage, outcome, location, site_type, height, duration, start_date, units, value_eur,
next_step, next_date, record, notes, log). Meta keys are prefixed `_fsp_`. Stage changes (board or form) append
`{t, from, to, outcome, user}` to `_fsp_log`.
Stages: contact, calificare, oferta, negociere, inchis (+ outcome castigat|pierdut).
Board: drag & drop (admin-ajax `fsp_move`), fallback dropdown on each card, search, “hide lost”, column count + EUR total
(lost deals excluded from total), overdue next-step date in red. Click a card to edit.
Questionnaire record → **Creează deal din fișă**: finds/creates the company and contact by name, prefills location, terrain,
height, duration, units (num_sites), next step, notes (start, budget, meeting notes), links the record, stage = Calificare.
