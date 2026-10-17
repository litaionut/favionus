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

# Pipeline CRM (kanban) – v0.3.0
Menu **Favionus Sales → Pipeline / Carduri / Companii / Contacte / Import-export CSV / Setări pipeline**.
Same rules: `manage_options` + nonce on every handler, admin-ajax (`fsp_move`, `fsp_note`, `fsp_timeline`; no `nopriv`) and
admin-post only, no REST/shortcode/front-end, private CPTs excluded from sitemaps, noindex on all screens.

## Columns (option `fsp_stages`)
Ordered list of `{key, label, kind, enabled}`; kind = `backlog` | `open` | `won` | `lost`.
Defaults: Backlog, Contactați (abordați), Au răspuns, Meeting avut, Follow-up, Vândut, Pierdut (toggle).
Settings screen: rename, reorder, add (kind open), delete only if empty and kind open; system columns can't be deleted;
Pierdut can be hidden only when empty. Moving into a won/lost column opens a modal (final value / loss reason + note).
Migration (runs once, option `fsp_db` = 2): contact→contactati, calificare→raspuns, oferta/negociere→followup,
inchis+pierdut→pierdut, inchis(+castigat)→vandut, unknown→contactati; v1 `_fsp_record` → `_fsp_records[]`; card contact
fields filled from linked company/contact. Each change is logged in the timeline as "Migrare coloane v2".

## Card (`fsp_deal`) = one contact / lead
Meta `_fsp_*`: contact_name, company_name, role, email, phone, website, project_name, country, location, mw,
project_type (greenfield|met_mast|repowering|site_investigation), notes, stage, source, value_eur, next_step, next_date,
contacts[] (extra), site_type, height, duration, start_date, units, records[] (questionnaire record ids), lost_reason,
log (timeline JSON: {t, type: stage|note|record|import|migrate, from, to, text, user}).
On save the card finds/creates the company (`_fsp_company`) by name and the primary contact (`_fsp_contact`) by
email or name+company, and copies role/email/phone to it. Card title = contact name (else company).

## Questionnaire ↔ card
"Pornește chestionarul" → `fsq-run&deal=ID`: client/company/location prefilled, banner shows the card; saving the record
links it (`_fsp_records` on the card, `_fsq_deal` on the record) and logs it. The card shows linked records with the
client offer-scope lines. A record without a card has "Creează card din fișă".

## CSV
Import: upload (.csv/.txt, ≤2 MB, finfo mime text/csv|text/plain…, ≤5000 rows, delimiter auto , ; tab, BOM, cp1250
fallback) → mapping with auto-match by header synonyms → options (target Backlog / any open column, dedupe by email
else company+name against existing cards and within the file, unmapped columns → notes, default source/country) →
preview with per-row status/warnings → import. Export: current board filter; cells starting with = + - @ tab CR are
prefixed with `'` (so phone numbers like +40… are exported as '+40…).
