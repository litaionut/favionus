<?php
/**
 * Romanian (/ro/) support without a multilingual plugin:
 * RO string map for the theme templates, lang="ro-RO", og:locale, hreflang, language switcher.
 *
 * If Polylang or WPML is installed later, the hreflang output and switcher step aside
 * (the plugin + Yoast handle them) so nothing is duplicated.
 *
 * TODO: native Romanian review of all strings below before publishing.
 *
 * @package doppler_lidar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EN path => RO path. Only pairs whose RO page is published are output as hreflang.
 */
function doppler_lidar_hreflang_pairs() {
	return array(
		'/'                                 => '/ro/',
		'/wind-lidar-measurement-campaigns/' => '/ro/campanii-masurare-vant-lidar/',
		'/lidar-or-a-met-mast/'             => '/ro/blog/lidar-sau-turn-de-masura/',
		'/how-turnkey-wind-lidar-campaign-works/' => '/ro/blog/cum-se-desfasoara-campanie-masurare-vant/',
	);
}

/**
 * Whether a multilingual plugin is active.
 */
function doppler_lidar_has_ml_plugin() {
	return function_exists( 'pll_current_language' ) || defined( 'ICL_SITEPRESS_VERSION' );
}

/**
 * Current request path, e.g. "/ro/".
 */
function doppler_lidar_current_path() {
	$path = wp_parse_url( home_url( add_query_arg( array() ) ), PHP_URL_PATH );
	if ( isset( $_SERVER['REQUEST_URI'] ) ) {
		$path = (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
	}
	return trailingslashit( '/' . ltrim( $path, '/' ) );
}

/**
 * Whether the current request is Romanian content.
 */
function doppler_lidar_is_ro() {
	static $is_ro = null;
	if ( null !== $is_ro ) {
		return $is_ro;
	}
	if ( function_exists( 'pll_current_language' ) ) {
		$is_ro = ( 'ro' === pll_current_language() );
		return $is_ro;
	}
	$is_ro = ( 0 === strpos( doppler_lidar_current_path(), '/ro/' ) );
	return $is_ro;
}

/**
 * Romanian strings for the theme text domain.
 */
function doppler_lidar_ro_strings() {
	return array(
		'Turnkey wind LiDAR campaigns' => 'Campanii LiDAR la cheie pentru vânt',
		'Bankable wind data.' => 'Date de vânt bancabile.',
		'Delivered turnkey.' => 'Campanii LiDAR la cheie.',
		'Complete wind LiDAR measurement campaigns for onshore wind projects, from campaign design and deployment to 24/7 remote monitoring and quality-controlled data delivery. Based in Bucharest, working in Romania and across Europe.' => 'Campanii complete de măsurare a vântului cu LiDAR pentru proiecte eoliene onshore, de la proiectarea campaniei și instalare până la monitorizare la distanță 24/7 și livrarea datelor verificate. Echipă cu sediul în București, cu proiecte în România și în Europa.',
		'Plan a campaign' => 'Planificați o campanie',
		'Check LiDAR availability' => 'Verificați disponibilitatea LiDAR',
		'Vertical profiling wind LiDAR unit' => 'LiDAR pentru vânt cu profil vertical',
		'Capabilities' => 'Capabilități',
		'Standard' => 'Standard',
		'IEC-Classified LiDAR Technology' => 'Tehnologie LiDAR clasificată IEC',
		'Uptime' => 'Disponibilitate',
		'24/7 Remote Monitoring' => 'Monitorizare la distanță 24/7',
		'Reliability' => 'Fiabilitate',
		'Autonomous Operation' => 'Funcționare autonomă',
		'Integrity' => 'Integritate',
		'Quality-Controlled Data' => 'Date cu control al calității',
		'Reliable wind data, less complexity' => 'Date de vânt fiabile, cu mai puțină complexitate',
		'A successful wind measurement campaign takes more than a LiDAR. Favionus combines vertical profiling LiDAR, autonomous power, remote monitoring and wind engineering expertise into one managed service. You get wind data for your project without running the campaign yourself. Prefer to run it in-house? We also offer wind LiDAR rental.' => 'O campanie de măsurare a vântului reușită înseamnă mai mult decât un LiDAR. Favionus combină LiDAR cu profil vertical, alimentare autonomă, monitorizare la distanță și expertiză în inginerie eoliană într-un singur serviciu administrat. Primiți datele de vânt pentru proiect fără să gestionați singuri campania. Preferați să o derulați intern? Oferim și închiriere LiDAR pentru vânt.',
		'Complete Campaign' => 'Campanie completă',
		'Planning, deployment, commissioning and decommissioning.' => 'Planificare, instalare, punere în funcțiune și dezafectare.',
		'Continuous Monitoring' => 'Monitorizare continuă',
		'Remote monitoring of equipment health and incoming wind data.' => 'Monitorizarea la distanță a stării echipamentelor și a datelor de vânt primite.',
		'Structured datasets and reporting ready for your wind resource assessment workflow.' => 'Seturi de date structurate și rapoarte pregătite pentru evaluarea potențialului eolian.',
		'From site to dataset: how a LiDAR campaign works' => 'De la amplasament la setul de date: cum se desfășoară o campanie LiDAR',
		'01 — Plan' => '01 — Planificare',
		'We define measurement objectives, heights, campaign duration and site requirements.' => 'Stabilim obiectivele măsurătorilor, înălțimile, durata campaniei și cerințele amplasamentului.',
		'02 — Deploy' => '02 — Instalare',
		'We transport, install and commission the LiDAR and its autonomous power system.' => 'Transportăm, instalăm și punem în funcțiune LiDAR-ul și sistemul său de alimentare autonomă.',
		'03 — Monitor' => '03 — Monitorizare',
		'System performance and incoming measurements are monitored continuously.' => 'Performanța sistemului și măsurătorile primite sunt monitorizate continuu.',
		'04 — Deliver' => '04 — Livrare',
		'Quality-controlled wind data and campaign reports throughout the project.' => 'Date de vânt verificate și rapoarte de campanie pe toată durata proiectului.',
		'Wind data you can base investment decisions on' => 'Date pe care vă puteți baza deciziile de investiție',
		'Wind measurements feed energy yield assessments, turbine selection and investment decisions, so campaign quality can\'t be an afterthought.' => 'Măsurătorile de vânt stau la baza estimării producției de energie, a alegerii turbinelor și a deciziilor de investiție, așa că, la o campanie, calitatea nu poate fi lăsată pe plan secundar.',
		'IEC-classified LiDAR technology' => 'Tehnologie LiDAR clasificată IEC',
		'Documented commissioning' => 'Punere în funcțiune documentată',
		'Remote monitoring' => 'Monitorizare la distanță',
		'Systematic quality control' => 'Control sistematic al calității',
		'Traceable campaign reporting' => 'Raportare trasabilă a campaniei',
		'Explore our data quality process' => 'Descoperiți procesul nostru de control al calității',
		'Wind Data Analytics Dashboard' => 'Panou de analiză a datelor de vânt',
		'Executive Summary (sample data)' => 'Rezumat (date exemplu)',
		'Example site | Sample data' => 'Amplasament exemplu | Date exemplu',
		'Status' => 'Stare',
		'Sample data' => 'Date exemplu',
		'Data Availability' => 'Disponibilitatea datelor',
		'Sample' => 'Exemplu',
		'7-Day Wind Speed Trend' => 'Viteza vântului, ultimele 7 zile',
		'Avg' => 'Medie',
		'Max Gust' => 'Rafală max.',
		'Key Campaign Averages' => 'Valori medii ale campaniei',
		'Parameter' => 'Parametru',
		'Height' => 'Înălțime',
		'Mean' => 'Medie',
		'Wind Speed' => 'Viteza vântului',
		'Wind Direction' => 'Direcția vântului',
		'Turbulence Intensity' => 'Intensitatea turbulenței',
		'Prevailing Direction' => 'Direcția dominantă',
		'South-West' => 'Sud-vest',
		'Built for onshore wind projects in Romania and Europe' => 'Pentru proiecte eoliene onshore din România și Europa',
		'Wind Resource Assessment' => 'Evaluarea potențialului eolian',
		'On-site wind measurements for greenfield wind farm development.' => 'Măsurători de vânt la fața locului pentru dezvoltarea de noi parcuri eoliene.',
		'Met Mast Complement' => 'Complement pentru turnul de măsură',
		'Measure across the full rotor-swept height alongside an existing mast.' => 'Măsurători pe toată înălțimea rotorului, alături de un turn de măsură existent.',
		'Repowering' => 'Repowering',
		'Measure wind conditions at modern turbine hub and rotor heights.' => 'Măsurarea vântului la înălțimea butucului și a rotorului turbinelor moderne.',
		'Site Investigation' => 'Investigarea amplasamentului',
		'Characterise prospective wind farm sites quickly, before you commit to a mast.' => 'Caracterizați rapid amplasamentele potențiale, înainte să investiți într-un turn de măsură.',
		'Technology' => 'Tehnologie',
		'Vertical profiling LiDAR technology' => 'Tehnologie LiDAR cu profil vertical',
		'We work with LiDAR units from leading manufacturers, matched to your site and hub height, as a full turnkey campaign or as wind LiDAR rental. Vertical profiling Doppler LiDAR measures the wind profile across modern turbine rotor heights, which makes it a practical alternative or complement to a tall met mast, without the tower and with fewer civil works.' => 'Lucrăm cu unități LiDAR de la producători de top, alese în funcție de amplasament și de înălțimea butucului, fie într-o campanie completă la cheie, fie prin închiriere LiDAR pentru vânt. LiDAR-ul Doppler cu profil vertical măsoară profilul vântului pe toată înălțimea rotorului turbinelor moderne. Este astfel o alternativă practică sau un complement pentru un turn de măsură înalt, fără turn și cu mai puține lucrări de construcție.',
		'Explore the technology' => 'Descoperiți tehnologia',
		'Vertical wind profiling LiDAR installed beside a wind farm' => 'LiDAR pentru vânt cu profil vertical instalat lângă un parc eolian',
		'Sample Campaign Report' => 'Exemplu de raport de campanie',
		'Transparent campaign reporting' => 'Raportare transparentă a campaniei',
		'Every campaign comes with clear reporting: monthly statistics, equipment status and quality-control observations, so you can see how the campaign is performing and how each dataset was produced.' => 'Fiecare campanie include rapoarte clare: statistici lunare, starea echipamentelor și observații de control al calității, ca să vedeți cum evoluează campania și cum a fost obținut fiecare set de date.',
		'Monthly statistics' => 'Statistici lunare',
		'Equipment status' => 'Starea echipamentelor',
		'QC observations' => 'Observații de control al calității',
		'View Sample Campaign Report' => 'Vedeți exemplul de raport',
		'Campaign Status Report (example)' => 'Raport de stare a campaniei (exemplu)',
		'Availability' => 'Disponibilitate',
		'Wind Rose Analysis' => 'Roza vânturilor',
		'Mean Wind Speed' => 'Viteza medie a vântului',
		'Turbulence category (example)' => 'Categoria de turbulență (exemplu)',
		'Plan your wind measurement campaign' => 'Planificați campania de măsurare a vântului',
		'Tell us about your site and timeline, and we\'ll plan the right LiDAR campaign for your project.' => 'Spuneți-ne despre amplasament și calendar, iar noi planificăm campania LiDAR potrivită pentru proiectul dumneavoastră.',
		'Message received. We will be in touch to discuss the right campaign for your project.' => 'Mesaj primit. Vă contactăm pentru a discuta campania potrivită pentru proiectul dumneavoastră.',
		'Please fill in your name and a valid work email, then try again.' => 'Completați numele și o adresă de e-mail de serviciu validă, apoi încercați din nou.',
		'Name' => 'Nume',
		'Work Email' => 'E-mail de serviciu',
		'Work email' => 'E-mail de serviciu',
		'Country' => 'Țara',
		'Expected Start' => 'Data estimată de începere',
		'Anything Else? (Optional)' => 'Alte detalii (opțional)',
		'Request a Campaign' => 'Solicitați o campanie',
		'Fleet' => 'Flotă',
		'Check availability' => 'Verificați disponibilitatea',
		'Close' => 'Închide',
		'Tell us where and how many units you need. We will show current fleet availability.' => 'Spuneți-ne unde și de câte unități aveți nevoie. Vă arătăm disponibilitatea actuală a flotei.',
		'Select country' => 'Selectați țara',
		'Estimated data need' => 'Perioada estimată de măsurare',
		'< 1 month' => '< 1 lună',
		'2–3 months' => '2–3 luni',
		'> 3 months' => '> 3 luni',
		'Number of units' => 'Număr de unități',
		'Request LiDAR availability' => 'Solicitați disponibilitatea LiDAR',
		'LiDAR availability' => 'Disponibilitate LiDAR',
		'Request availability' => 'Solicitați disponibilitatea',
		'Tell us about your project and how many LiDAR units you need. We\'ll confirm availability by email within 1 business day.' => 'Spuneți-ne despre proiect și de câte unități LiDAR aveți nevoie. Vă confirmăm disponibilitatea prin e-mail în cel mult o zi lucrătoare.',
		'Project location (region or nearest town)' => 'Locația proiectului (regiune sau cea mai apropiată localitate)',
		'Preferred start date' => 'Data de începere dorită',
		'Measurement period' => 'Perioada de măsurare',
		'This is a request, not a booking. We will reply by email with availability and options.' => 'Aceasta este o solicitare, nu o rezervare. Vă răspundem prin e-mail cu disponibilitatea și opțiunile.',
		'Thank you. We received your request and will confirm availability by email within 1 business day.' => 'Vă mulțumim. Am primit solicitarea și vă confirmăm disponibilitatea prin e-mail în cel mult o zi lucrătoare.',
		'Please fill in all fields with a valid work email, then try again.' => 'Completați toate câmpurile, cu o adresă de e-mail de serviciu validă, apoi încercați din nou.',
		'LiDARs are available for the checked conditions. Leave your details and we will contact you for a personalised discussion.' => 'Există unități LiDAR disponibile pentru condițiile selectate. Lăsați-ne datele de contact și vă contactăm pentru o discuție personalizată.',
		'Personalised solution' => 'Soluție personalizată',
		'For more than two units, we will prepare a tailored offer. Leave your details and we will come back with options.' => 'Pentru mai mult de două unități pregătim o ofertă personalizată. Lăsați-ne datele de contact și revenim cu opțiuni.',
		'Company' => 'Companie',
		'Back' => 'Înapoi',
		'Send enquiry' => 'Trimiteți solicitarea',
		'Thank you. We received your enquiry and will be in touch.' => 'Vă mulțumim. Am primit solicitarea și vă vom contacta.',
		'Bucharest, Romania' => 'București, România',
		'Contact' => 'Contact',
		'Data Quality' => 'Calitatea datelor',
		'How it works' => 'Cum funcționează',
		'Legal Information' => 'Informații legale',
		'Privacy' => 'Confidențialitate',
		'Research & Blog' => 'Resurse și blog',
		'Services' => 'Servicii',
		'Skip to content' => 'Salt la conținut',
		'Terms' => 'Termeni',
		'Use Cases' => 'Aplicații',
		'Favionus. All rights reserved.' => 'Favionus. Toate drepturile rezervate.',
		'Turnkey LiDAR wind measurement campaigns for onshore wind projects across Europe.' => 'Campanii LiDAR la cheie pentru măsurarea vântului în proiecte eoliene onshore din România și Europa.',
	);
}

/**
 * Translate theme strings on Romanian pages (only if no .mo / plugin translation exists).
 *
 * @param string $translation Translated text.
 * @param string $text        Original text.
 * @param string $domain      Text domain.
 * @return string
 */
function doppler_lidar_ro_gettext( $translation, $text, $domain ) {
	if ( 'doppler_lidar' !== $domain || $translation !== $text || ! doppler_lidar_is_ro() ) {
		return $translation;
	}
	static $map = null;
	if ( null === $map ) {
		$map = doppler_lidar_ro_strings();
	}
	return isset( $map[ $text ] ) ? $map[ $text ] : $translation;
}
add_filter( 'gettext', 'doppler_lidar_ro_gettext', 10, 3 );

/**
 * lang="ro-RO" on Romanian pages.
 *
 * @param string $output Language attributes.
 * @return string
 */
function doppler_lidar_ro_language_attributes( $output ) {
	if ( doppler_lidar_has_ml_plugin() || ! doppler_lidar_is_ro() ) {
		return $output;
	}
	return preg_replace( '/lang="[^"]*"/', 'lang="ro-RO"', $output );
}
add_filter( 'language_attributes', 'doppler_lidar_ro_language_attributes' );

/**
 * og:locale ro_RO.
 *
 * @param string $locale Locale.
 * @return string
 */
function doppler_lidar_ro_og_locale( $locale ) {
	return ( ! doppler_lidar_has_ml_plugin() && doppler_lidar_is_ro() ) ? 'ro_RO' : $locale;
}
add_filter( 'wpseo_og_locale', 'doppler_lidar_ro_og_locale' );

/**
 * Whether a path resolves to a published page or post.
 *
 * @param string $path Path like "/ro/".
 */
function doppler_lidar_path_published( $path ) {
	if ( '/' === $path ) {
		return true;
	}
	$id = url_to_postid( home_url( $path ) );
	return $id && 'publish' === get_post_status( $id );
}

/**
 * The alternate pair for the current page: array( 'en' => path, 'ro' => path ) or null.
 */
function doppler_lidar_current_pair() {
	$path = doppler_lidar_current_path();
	foreach ( doppler_lidar_hreflang_pairs() as $en => $ro ) {
		if ( $path === $en || $path === $ro ) {
			if ( doppler_lidar_path_published( $en ) && doppler_lidar_path_published( $ro ) ) {
				return array( 'en' => $en, 'ro' => $ro );
			}
			return null;
		}
	}
	return null;
}

/**
 * Print reciprocal hreflang tags (EN, RO, x-default = EN).
 */
function doppler_lidar_print_hreflang() {
	if ( doppler_lidar_has_ml_plugin() || is_paged() || is_search() ) {
		return;
	}
	$pair = doppler_lidar_current_pair();
	if ( ! $pair ) {
		return;
	}
	printf( '<link rel="alternate" hreflang="en" href="%s" />' . "\n", esc_url( home_url( $pair['en'] ) ) );
	printf( '<link rel="alternate" hreflang="ro" href="%s" />' . "\n", esc_url( home_url( $pair['ro'] ) ) );
	printf( '<link rel="alternate" hreflang="x-default" href="%s" />' . "\n", esc_url( home_url( $pair['en'] ) ) );
}
add_action( 'wp_head', 'doppler_lidar_print_hreflang', 2 );

/**
 * Header language switcher (EN | RO). Links to the counterpart when it exists, else the other home.
 */
function doppler_lidar_language_switcher() {
	if ( function_exists( 'pll_the_languages' ) ) {
		echo '<ul class="wd-lang">';
		pll_the_languages( array( 'display_names_as' => 'slug' ) );
		echo '</ul>';
		return;
	}
	if ( ! doppler_lidar_path_published( '/ro/' ) ) {
		return; // No Romanian site yet: show nothing.
	}
	$pair  = doppler_lidar_current_pair();
	$is_ro = doppler_lidar_is_ro();
	$en    = $pair ? $pair['en'] : '/';
	$ro    = $pair ? $pair['ro'] : '/ro/';
	printf(
		'<nav class="wd-lang" aria-label="%1$s"><a href="%2$s" hreflang="en" lang="en"%3$s>EN</a> <span aria-hidden="true">|</span> <a href="%4$s" hreflang="ro" lang="ro"%5$s>RO</a></nav>',
		esc_attr__( 'Language', 'doppler_lidar' ),
		esc_url( home_url( $en ) ),
		$is_ro ? '' : ' aria-current="true"',
		esc_url( home_url( $ro ) ),
		$is_ro ? ' aria-current="true"' : ''
	);
}

/**
 * Home URL for the current language (used for "Plan a campaign" and anchor links).
 *
 * @param string $suffix Path or fragment, e.g. "#contact".
 */
function doppler_lidar_lang_home( $suffix = '' ) {
	$base = doppler_lidar_is_ro() ? home_url( '/ro/' ) : home_url( '/' );
	return $base . ltrim( $suffix, '/' );
}
