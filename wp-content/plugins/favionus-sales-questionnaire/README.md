# Favionus Sales Questionnaire (admin-only)

Guided sales-meeting questionnaire in wp-admin → **Chestionar vânzări**. Visible only to users with `manage_options`.
No front-end output, no shortcode, no REST route; saving uses `admin-ajax` (`wp_ajax_fsq_save`, no `nopriv`) with capability + nonce;
deleting uses `admin-post` with capability + nonce. Records are a private CPT `favionus_sales_rec` (not public, not queryable,
not in search/REST/sitemaps, no rewrite). Admin screens send `X-Robots-Tag: noindex` and a robots meta tag.

## Question tree – `question-tree.json`
```json
{
  "schema": 1,
  "start": "<node id>",
  "nodes": [
    { "id": "q_x", "type": "choice", "text_ro": "…", "text_en": "…", "help_ro": "optional hint",
      "options": [ { "label_ro": "…", "label_en": "…", "value": "machine_value", "next": "q_y" } ],
      "next": "default next for options without their own next (optional)" },
    { "id": "q_y", "type": "number", "text_ro": "…", "min": 1, "max": 50, "unit": "luni", "required": true, "next": "q_z" },
    { "id": "q_z", "type": "text", "text_ro": "…", "placeholder_ro": "…", "required": false, "next": "end_a" },
    { "id": "end_a", "type": "end", "text_ro": "Outcome / recommendation shown on the summary" }
  ]
}
```
* `id`: unique, lowercase `[a-z0-9_-]`. `type`: `choice | text | number | end`.
* Any text field `X` may be given as `X_ro` and/or `X_en`; Romanian is shown, English is the fallback.
* `choice`: `options[].next` overrides node `next`. `text`/`number`: `next` required. `end`: shows summary.

The shipped tree is an **EXAMPLE**. Validate a real tree before deploying:
`node tools/validate-tree.js /workspace/sales-form/question-tree.json`
(errors: bad structure, duplicate/dangling ids, missing start/end; warnings: cycles, unreachable nodes, dead ends).
To replace the tree, upload the new `question-tree.json` over the plugin's file (File Manager) – no code change needed.
