# favionus.com

Copie doar pentru citire a site-ului WordPress de la https://favionus.com/, luată din `public_html`.

Site-ul live nu a fost modificat. În git nu intră `wp-config.php`, copiile `.bak`, dump-urile SQL, jurnalele, cache-ul, arhiva de backup și fișierele parțiale.

Pentru o copie locală este nevoie de un `wp-config.php` propriu și de o bază de date separată.

## Fundație SEO

Pachetul SEO stă peste arborele WordPress importat. Tema, pluginurile și conținutul live rămân neschimbate.

| Cale | Rol |
| --- | --- |
| `wp-content/mu-plugins/favionus-seo.php` | Redirect HTTPS, titlu și meta, Open Graph, schema, noindex pentru Hello World, `/llms.txt` și `/ai.txt` |
| `content/pages/` | Schițe pentru paginile de serviciu și cele legale |
| `content/blog/` | Trei schițe de articole |
| `seo/yoast-wp-checklist.md` | Pași în Yoast și Search Console |

Site-ul live nu a fost modificat de acest pachet. Publicarea se face doar după merge.
