# Yoast + WP Admin SEO checklist (Favionus)

Apply after the WordPress import is on the server and `mu-plugins/favionus-seo.php` is deployed.

## 1. Site identity (Settings → General)

| Field | Value |
| --- | --- |
| Site Title | Favionus |
| Tagline | Turnkey wind LiDAR measurement campaigns — bankable data |

## 2. Permalinks

Use **Post name** (`/%postname%/`). Flush permalinks after deploying the MU plugin (Settings → Permalinks → Save) so `/llms.txt` and `/ai.txt` resolve.

## 3. Yoast → Search appearance

### Homepage (or static front page SEO)

- **SEO title:** `Wind LiDAR Measurement Campaigns | Bankable Data | Favionus`
- **Meta description:** `Turnkey onshore wind LiDAR measurement campaigns — IEC-classified vertical profiling, remote monitoring, and quality-controlled bankable wind data for resource assessment.`
- **Social image:** upload a dedicated **1200×630** JPG/PNG (do not rely on the 620×573 theme WebP long-term)

### Organization

- Organization name: Favionus  
- Logo: square brand mark ≥ 112×112  
- Organization description: same as meta description above  

### Content types

- Disable author archives if only one author (or noindex them)  
- Keep pages + posts in the sitemap  
- Exclude `hello-world` (delete the post) and `Uncategorized` if unused  

## 4. Delete / noindex junk

1. Trash **Hello world!** permanently  
2. Fix Terms / Privacy footer links to `/terms-of-use/` and `/privacy-policy/`  
3. Replace placeholder phone `+40 722 000 000` with the real number  
4. Point LinkedIn footer to the real company URL (currently `https://www.linkedin.com/`)  

## 5. Create pages (from `content/pages/`)

| Slug | Title | Primary keyword |
| --- | --- | --- |
| `wind-lidar-measurement-campaigns` | Wind LiDAR Measurement Campaigns | wind LiDAR measurement campaign |
| `wind-resource-assessment` | Wind Resource Assessment with LiDAR | LiDAR wind resource assessment |
| `met-mast-complement` | Met Mast Complement | met mast LiDAR complement |
| `repowering-wind-measurement` | Repowering Wind Measurement | repowering wind measurement LiDAR |
| `privacy-policy` | Privacy Policy | (brand) |
| `terms-of-use` | Terms of Use | (brand) |

Publish three blog posts from `content/blog/`. Set category e.g. `Wind measurement` (not Uncategorized).

## 6. MonsterInsights

Authenticate GA4. Until then the tracking snippet is empty (confirmed on live HTML).

## 7. Search Console & Bing

1. Verify `https://favionus.com/`  
2. Submit `https://favionus.com/sitemap_index.xml`  
3. Request indexing for `/` and each new service page  
4. Fix HTTP→HTTPS at host/Cloudflare if the MU plugin is not yet live (bare `http://` currently returns **200**)

## 8. Cloudflare / Bluehost

- Always Use HTTPS / Automatic HTTPS Rewrites  
- Enable HSTS once HTTPS redirect is confirmed  
- Cache HTML carefully while SEO changes roll out  

## 9. After deploy smoke test

```bash
curl -sI http://favionus.com/ | head -5          # expect 301 → https
curl -sL https://favionus.com/ | grep -E '<title>|meta name=.description|og:image|ProfessionalService'
curl -sL https://favionus.com/llms.txt | head
curl -sL https://favionus.com/hello-world/ | grep -i robots   # expect noindex if post still exists
curl -sL https://favionus.com/robots.txt
```
