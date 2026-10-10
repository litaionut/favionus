# SEO metadata and editor notes for content/ drafts

Kept out of the .md files because mu-plugins/favionus-seo.php converts those files into page HTML (any comment would show up as visible text).

## content/pages/wind-lidar-measurement-campaigns.md
- Slug: /wind-lidar-measurement-campaigns/   Template: full width
- SEO title (Yoast): Wind Measurement Campaigns in Romania with LiDAR (48 chars, per seo-romania-plan.md)
- Meta description (Yoast): Turnkey onshore wind LiDAR campaigns and wind LiDAR rental: LiDAR units from leading manufacturers, autonomous power, monitoring and QC'd data. (143 chars)
- TODO [confirm]: "Data quality and standards" section (IEC 61400-50-2 classification and verification process) and "Deliverables" section (formats, intervals). Waiting for Florin.
- TODO [confirm]: FAQ (campaign duration, terrain suitability, permits in Romania, countries, number of units).

## content/pages/met-mast-complement.md
- Slug: /met-mast-complement/   Template: full width
- SEO title (Yoast): Met Mast Complement: LiDAR for Rotor-Height Wind Data
- Meta description (Yoast): Complement your met mast with vertical profiling LiDAR to measure across the rotor-swept height and reduce shear extrapolation uncertainty.

## content/blog/lidar-vs-met-mast-wind-resource-assessment.md
- New angle (bankability/uncertainty), to avoid cannibalising the live post /lidar-or-a-met-mast/.
- Slug: TODO [confirm] proposed /blog/lidar-met-mast-bankability/ (alternative: merge into what-makes-wind-lidar-data-bankable.md)
- SEO title (Yoast): LiDAR vs Met Mast: Uncertainty & Bankability Compared
- Meta description (Yoast): How LiDAR and met mast data compare for bankable wind resource assessment: verification, IEC 61400-50-2, uncertainty and when to combine both.

## content/pages/privacy-policy.md
- Slug: /privacy-policy/   Template: Full width (no sidebar). Set as Settings › Privacy › privacy policy page.
- SEO title: Privacy Policy – Favionus. Meta description: How Favionus S.R.L. collects and uses personal data from website enquiries, and your GDPR rights.
- TODO: add the ONRC number, CUI and full registered address once Florin provides them. Native legal review recommended.

## content/pages/terms-of-use.md
- Slug: /terms-of-use/   Template: Full width (no sidebar).
- SEO title: Terms of Use – Favionus. Meta description: Terms for using favionus.com: information only, offers by written proposal, Romanian law.

## Romanian pages (PR 3). Create in wp-admin when publishing resumes. TODO: native Romanian review.
### /ro/ (RO homepage)
- Create page "Acasă" with slug `ro`, template **Homepage (Romanian)**. Leave the content empty: the template renders the translated homepage.
- SEO title: Campanii de măsurare a vântului cu LiDAR – Favionus (51)
- Meta description: Campanii LiDAR la cheie pentru măsurarea vântului în proiecte eoliene onshore din România: instalare, monitorizare 24/7 și date verificate. (139)
### content/ro/pages/campanii-masurare-vant-lidar.md
- Create page with slug `campanii-masurare-vant-lidar`, **parent = the "ro" page** (URL /ro/campanii-masurare-vant-lidar/), template Full width (from PR 2; Default works too).
- SEO title: Campanie de măsurare a vântului cu LiDAR – Favionus (51)
- Meta description: Campanii de măsurare a vântului și închiriere LiDAR pentru parcuri eoliene din România: LiDAR cu profil vertical, alimentare autonomă, date verificate. (149; the plan's "IEC 61400-50-2" wording was replaced because it is still [confirm])
- Left out pending [confirm]: "Calitatea datelor și IEC 61400-50-2" (verification procedure), "Ce livrăm" (formats), FAQ (duration, permits in Romania).
- hreflang: emitted automatically by inc/seo-ro.php once both /wind-lidar-measurement-campaigns/ and this page are published.

## Romanian articles (PR 5). Create as posts in wp-admin when publishing resumes. TODO: native Romanian review.
For the /ro/blog/... URLs without a multilingual plugin: Settings › Permalinks › Custom structure is site-wide, so either (a) install Polylang (recommended; RO posts get /ro/ automatically, with a custom "blog" base), or (b) publish them as child pages of /ro/ → "blog" (page slug `blog`, parent `ro`). Option (b) matches the hreflang map in PR 3.
### content/ro/blog/lidar-sau-turn-de-masura.md → /ro/blog/lidar-sau-turn-de-masura/
- SEO title: LiDAR sau turn de măsură? Cum alegeți pentru proiect (52)
- Meta description: Cine decide, complexitatea terenului, înălțimi, autorizații și bancabilitate: ghid practic pentru alegerea între LiDAR, turn de măsură sau ambele. (146)
- Adapted from the live EN post. The fixed "400 m" range was removed. The [n] citation markers were dropped until the reference list is confirmed. Remove the two HTML TODO comments before pasting.
- TODO [confirm]: Romanian permits section (building permit, AACR) and the References list. Author: a real named person.
### content/ro/blog/cum-se-desfasoara-campanie-masurare-vant.md → /ro/blog/cum-se-desfasoara-campanie-masurare-vant/
- SEO title: Cum se desfășoară o campanie de măsurare a vântului (51)
- Meta description: De la obiective și înălțimi la instalare, monitorizare și livrarea datelor: etapele unei campanii LiDAR de măsurare a vântului pentru un parc eolian. (149)
- Adapted from content/blog/how-turnkey-wind-lidar-campaign-works.md (no new claims).
