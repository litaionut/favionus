<?php
/**
 * doppler_lidar functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package doppler_lidar
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.8.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function doppler_lidar_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on doppler_lidar, use a find and replace
		* to change 'doppler_lidar' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'doppler_lidar', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'doppler_lidar' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'doppler_lidar_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'doppler_lidar_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function doppler_lidar_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'doppler_lidar_content_width', 1200 );
}
add_action( 'after_setup_theme', 'doppler_lidar_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function doppler_lidar_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'doppler_lidar' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'doppler_lidar' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'doppler_lidar_widgets_init' );

/**
 * Allowed design skins.
 *
 * @return string[]
 */
function doppler_lidar_skins() {
	return array( 'vane', 'classic' );
}

/**
 * Current design skin slug.
 *
 * @return string
 */
function doppler_lidar_get_skin() {
	$skin = get_theme_mod( 'doppler_lidar_skin', 'vane' );
	$skins = doppler_lidar_skins();
	return in_array( $skin, $skins, true ) ? $skin : 'vane';
}

/**
 * Sanitize Customizer skin choice.
 *
 * @param string $value Raw setting value.
 * @return string
 */
function doppler_lidar_sanitize_skin( $value ) {
	return in_array( $value, doppler_lidar_skins(), true ) ? $value : 'vane';
}

/**
 * Image URL for the active skin.
 *
 * @param string $name Base file name without skin suffix (e.g. lidar).
 * @return string
 */
function doppler_lidar_skin_image( $name ) {
	$rel  = '/images/' . $name . '-' . doppler_lidar_get_skin() . '.svg';
	$path = get_template_directory() . $rel;
	$ver  = file_exists( $path ) ? (string) filemtime( $path ) : _S_VERSION;
	return get_template_directory_uri() . $rel . '?ver=' . rawurlencode( $ver );
}

/**
 * Enqueue scripts and styles.
 */
function doppler_lidar_scripts() {
	$skin = doppler_lidar_get_skin();
	$fonts = 'vane' === $skin
		? 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap'
		: 'https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;600;700;800&family=JetBrains+Mono:wght@500&display=swap';

	wp_enqueue_style(
		'doppler_lidar-fonts',
		$fonts,
		array(),
		null
	);
	wp_enqueue_style(
		'doppler_lidar-icons',
		'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap',
		array(),
		null
	);

	wp_enqueue_style( 'doppler_lidar-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_enqueue_style(
		'doppler_lidar-main',
		get_template_directory_uri() . '/css/main.css',
		array( 'doppler_lidar-style', 'doppler_lidar-fonts' ),
		_S_VERSION
	);
	wp_enqueue_style(
		'doppler_lidar-skin',
		get_template_directory_uri() . '/css/skin-' . $skin . '.css',
		array( 'doppler_lidar-main' ),
		_S_VERSION
	);

	if ( 'vane' === $skin ) {
		$texture = get_template_directory_uri() . '/images/felt.png';
		$css     = sprintf(
			'html,body,body.custom-background,.site-header,.main-navigation.toggled ul{background-color:#f4f4f4!important;background-image:url("%s")!important;background-repeat:repeat;}',
			esc_url( $texture )
		);
		wp_add_inline_style( 'doppler_lidar-skin', $css );
	}

	wp_enqueue_script( 'doppler_lidar-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );

	if ( is_front_page() ) {
		wp_enqueue_script(
			'doppler_lidar-fleet',
			get_template_directory_uri() . '/js/fleet-modal.js',
			array(),
			_S_VERSION,
			true
		);
		wp_localize_script(
			'doppler_lidar-fleet',
			'dopplerLidarFleet',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'error'         => __( 'Please fill in your name, a valid work email and company, then try again.', 'doppler_lidar' ),
				'campaignSent'  => __( 'Message received. We will come back to discuss the most suited campaign for your project.', 'doppler_lidar' ),
				'campaignError' => __( 'Please fill in your name and a valid work email, then try again.', 'doppler_lidar' ),
				'noCountry' => __( 'No matching country', 'doppler_lidar' ),
				'durations' => array(
					'lt1' => __( '< 1 month', 'doppler_lidar' ),
					'2-3' => __( '2–3 months', 'doppler_lidar' ),
					'gt3' => __( '> 3 months', 'doppler_lidar' ),
				),
			)
		);
	}

	if ( is_front_page() && 'vane' === $skin ) {
		wp_enqueue_script(
			'doppler_lidar-scan',
			get_template_directory_uri() . '/js/lidar-scan.js',
			array(),
			_S_VERSION,
			true
		);
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'doppler_lidar_scripts' );

/**
 * Load Material Symbols asynchronously (non-blocking for LCP).
 *
 * @param string $html   Link tag HTML.
 * @param string $handle Style handle.
 * @return string
 */
function doppler_lidar_async_icon_css( $html, $handle ) {
	if ( 'doppler_lidar-icons' !== $handle ) {
		return $html;
	}
	$async = preg_replace( '/\smedia=(["\'])all\1/', ' media="print" onload="this.media=\'all\'"', $html, 1 );
	if ( ! is_string( $async ) || $async === $html ) {
		return $html;
	}
	return $async . '<noscript>' . $html . '</noscript>';
}
add_filter( 'style_loader_tag', 'doppler_lidar_async_icon_css', 10, 2 );

/**
 * Preconnect / preload critical assets for faster mobile LCP (header text + texture).
 */
function doppler_lidar_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array(
			'href'        => 'https://fonts.googleapis.com',
			'crossorigin' => 'anonymous',
		);
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'doppler_lidar_resource_hints', 10, 2 );

/**
 * Preload theme CSS + felt texture on the front page (LCP path).
 */
function doppler_lidar_preload_lcp_assets() {
	if ( ! is_front_page() ) {
		return;
	}
	$main = get_template_directory_uri() . '/css/main.css?ver=' . rawurlencode( _S_VERSION );
	$felt = get_template_directory_uri() . '/images/felt.png?ver=' . rawurlencode( _S_VERSION );
	printf(
		'<link rel="preload" href="%s" as="style" />' . "\n",
		esc_url( $main )
	);
	printf(
		'<link rel="preload" href="%s" as="image" type="image/png" fetchpriority="high" />' . "\n",
		esc_url( $felt )
	);
}
add_action( 'wp_head', 'doppler_lidar_preload_lcp_assets', 1 );

/**
 * Contact Form 7 is not used in theme templates — dequeue its CSS/JS sitewide when inactive on the view.
 */
function doppler_lidar_dequeue_unused_cf7() {
	if ( is_admin() ) {
		return;
	}
	wp_dequeue_style( 'contact-form-7' );
	wp_deregister_style( 'contact-form-7' );
	wp_dequeue_script( 'contact-form-7' );
	wp_deregister_script( 'contact-form-7' );
}
add_action( 'wp_enqueue_scripts', 'doppler_lidar_dequeue_unused_cf7', 100 );

/**
 * Fallback primary navigation matching the homepage mockup.
 */
function doppler_lidar_primary_menu_fallback() {
	$items = array(
		home_url( '/#technology' )    => __( 'LiDAR Systems', 'doppler_lidar' ),
		home_url( '/#how-it-works' )  => __( 'Measurement', 'doppler_lidar' ),
		home_url( '/#data-quality' )  => __( 'Analytics', 'doppler_lidar' ),
		home_url( '/#use-cases' )     => __( 'Compliance', 'doppler_lidar' ),
		doppler_lidar_resources_url() => __( 'Research & Blog', 'doppler_lidar' ),
		home_url( '/#contact' )       => __( 'Contact', 'doppler_lidar' ),
	);
	echo '<ul id="primary-menu" class="nav-menu">';
	foreach ( $items as $url => $label ) {
		printf(
			'<li><a href="%s">%s</a></li>',
			esc_url( $url ),
			esc_html( $label )
		);
	}
	echo '</ul>';
}

/**
 * True when the anti-spam honeypot was filled (bots only).
 *
 * @return bool
 */
function doppler_lidar_honeypot_filled() {
	foreach ( array( 'wd_hp_fax', 'company_website' ) as $key ) {
		if ( ! empty( $_POST[ $key ] ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Send a campaign request email.
 *
 * @return true|WP_Error
 */
function doppler_lidar_send_campaign_request() {
	if ( ! isset( $_POST['doppler_lidar_campaign_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['doppler_lidar_campaign_nonce'] ) ), 'doppler_lidar_campaign' ) ) {
		return new WP_Error( 'nonce', __( 'Please refresh the page and try again.', 'doppler_lidar' ) );
	}

	if ( doppler_lidar_honeypot_filled() ) {
		return true;
	}

	$name     = isset( $_POST['campaign_name'] ) ? sanitize_text_field( wp_unslash( $_POST['campaign_name'] ) ) : '';
	$email    = isset( $_POST['campaign_email'] ) ? sanitize_email( wp_unslash( $_POST['campaign_email'] ) ) : '';
	$location = isset( $_POST['campaign_location'] ) ? sanitize_text_field( wp_unslash( $_POST['campaign_location'] ) ) : '';
	$start    = isset( $_POST['campaign_start'] ) ? sanitize_text_field( wp_unslash( $_POST['campaign_start'] ) ) : '';
	$notes    = isset( $_POST['campaign_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['campaign_notes'] ) ) : '';

	if ( '' === $name || ! is_email( $email ) ) {
		return new WP_Error( 'invalid', __( 'Please fill in your name and a valid work email, then try again.', 'doppler_lidar' ) );
	}

	$lines = array(
		'Name: ' . $name,
		'Email: ' . $email,
		'Country: ' . $location,
		'Expected start: ' . $start,
		'Notes: ' . $notes,
	);

	$sent = wp_mail(
		get_option( 'admin_email' ),
		sprintf( '[Favionus] Campaign request from %s', $name ),
		implode( "\n", $lines ),
		array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $email )
	);

	if ( ! $sent ) {
		return new WP_Error( 'mail', __( 'We could not send your enquiry. Please try again.', 'doppler_lidar' ) );
	}

	return true;
}

/**
 * Handle homepage campaign request form (non-AJAX fallback).
 */
function doppler_lidar_handle_campaign_request() {
	if ( ! isset( $_POST['doppler_lidar_campaign_nonce'] ) ) {
		return;
	}

	$result = doppler_lidar_send_campaign_request();
	if ( is_wp_error( $result ) && 'nonce' === $result->get_error_code() ) {
		return;
	}

	wp_safe_redirect( add_query_arg( 'campaign', is_wp_error( $result ) ? 'error' : 'sent', home_url( '/' ) ) );
	exit;
}
add_action( 'template_redirect', 'doppler_lidar_handle_campaign_request' );

/**
 * Handle campaign request via AJAX so the visitor stays on #contact.
 */
function doppler_lidar_ajax_campaign_request() {
	$result = doppler_lidar_send_campaign_request();
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 'nonce' === $result->get_error_code() ? 403 : 400 );
	}
	wp_send_json_success();
}
add_action( 'wp_ajax_doppler_lidar_campaign', 'doppler_lidar_ajax_campaign_request' );
add_action( 'wp_ajax_nopriv_doppler_lidar_campaign', 'doppler_lidar_ajax_campaign_request' );

/**
 * Countries offered in the fleet availability modal.
 *
 * @return array<string, string>
 */
function doppler_lidar_fleet_countries() {
	return array(
		'AL'    => __( 'Albania', 'doppler_lidar' ),
		'AT'    => __( 'Austria', 'doppler_lidar' ),
		'BE'    => __( 'Belgium', 'doppler_lidar' ),
		'BA'    => __( 'Bosnia and Herzegovina', 'doppler_lidar' ),
		'BG'    => __( 'Bulgaria', 'doppler_lidar' ),
		'HR'    => __( 'Croatia', 'doppler_lidar' ),
		'CY'    => __( 'Cyprus', 'doppler_lidar' ),
		'CZ'    => __( 'Czechia', 'doppler_lidar' ),
		'DK'    => __( 'Denmark', 'doppler_lidar' ),
		'EE'    => __( 'Estonia', 'doppler_lidar' ),
		'FI'    => __( 'Finland', 'doppler_lidar' ),
		'FR'    => __( 'France', 'doppler_lidar' ),
		'DE'    => __( 'Germany', 'doppler_lidar' ),
		'GR'    => __( 'Greece', 'doppler_lidar' ),
		'HU'    => __( 'Hungary', 'doppler_lidar' ),
		'IE'    => __( 'Ireland', 'doppler_lidar' ),
		'IT'    => __( 'Italy', 'doppler_lidar' ),
		'XK'    => __( 'Kosovo', 'doppler_lidar' ),
		'LV'    => __( 'Latvia', 'doppler_lidar' ),
		'LT'    => __( 'Lithuania', 'doppler_lidar' ),
		'LU'    => __( 'Luxembourg', 'doppler_lidar' ),
		'MT'    => __( 'Malta', 'doppler_lidar' ),
		'MD'    => __( 'Moldova', 'doppler_lidar' ),
		'ME'    => __( 'Montenegro', 'doppler_lidar' ),
		'NL'    => __( 'Netherlands', 'doppler_lidar' ),
		'MK'    => __( 'North Macedonia', 'doppler_lidar' ),
		'NO'    => __( 'Norway', 'doppler_lidar' ),
		'PL'    => __( 'Poland', 'doppler_lidar' ),
		'PT'    => __( 'Portugal', 'doppler_lidar' ),
		'RO'    => __( 'Romania', 'doppler_lidar' ),
		'RS'    => __( 'Serbia', 'doppler_lidar' ),
		'SK'    => __( 'Slovakia', 'doppler_lidar' ),
		'SI'    => __( 'Slovenia', 'doppler_lidar' ),
		'ES'    => __( 'Spain', 'doppler_lidar' ),
		'SE'    => __( 'Sweden', 'doppler_lidar' ),
		'CH'    => __( 'Switzerland', 'doppler_lidar' ),
		'TR'    => __( 'Türkiye', 'doppler_lidar' ),
		'UA'    => __( 'Ukraine', 'doppler_lidar' ),
		'GB'    => __( 'United Kingdom', 'doppler_lidar' ),
		'OTHER' => __( 'Other', 'doppler_lidar' ),
	);
}

/**
 * Human-readable campaign duration from the fleet form.
 *
 * @param string $key Duration key.
 * @return string
 */
function doppler_lidar_fleet_duration_label( $key ) {
	$map = array(
		'lt1' => __( '< 1 month', 'doppler_lidar' ),
		'2-3' => __( '2–3 months', 'doppler_lidar' ),
		'gt3' => __( '> 3 months', 'doppler_lidar' ),
	);
	return isset( $map[ $key ] ) ? $map[ $key ] : $key;
}

/**
 * Handle fleet availability lead form (AJAX).
 */
function doppler_lidar_handle_fleet_request() {
	if ( ! isset( $_POST['doppler_lidar_fleet_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['doppler_lidar_fleet_nonce'] ) ), 'doppler_lidar_fleet' ) ) {
		wp_send_json_error( array( 'message' => __( 'Please refresh the page and try again.', 'doppler_lidar' ) ), 403 );
	}

	if ( doppler_lidar_honeypot_filled() ) {
		wp_send_json_success();
	}

	$name     = isset( $_POST['fleet_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fleet_name'] ) ) : '';
	$email    = isset( $_POST['fleet_email'] ) ? sanitize_email( wp_unslash( $_POST['fleet_email'] ) ) : '';
	$company  = isset( $_POST['fleet_company'] ) ? sanitize_text_field( wp_unslash( $_POST['fleet_company'] ) ) : '';
	$country  = isset( $_POST['fleet_country'] ) ? sanitize_text_field( wp_unslash( $_POST['fleet_country'] ) ) : '';
	$duration = isset( $_POST['fleet_duration'] ) ? sanitize_text_field( wp_unslash( $_POST['fleet_duration'] ) ) : '';
	$units    = isset( $_POST['fleet_units'] ) ? absint( $_POST['fleet_units'] ) : 0;

	$allowed_durations = array( 'lt1', '2-3', 'gt3' );
	$countries         = doppler_lidar_fleet_countries();
	if ( '' === $name || ! is_email( $email ) || '' === $company || $units < 1 || ! isset( $countries[ $country ] ) || ! in_array( $duration, $allowed_durations, true ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Please fill in your name, a valid work email and company, then try again.', 'doppler_lidar' ) ),
			400
		);
	}

	$outcome = isset( $_POST['fleet_outcome'] ) ? sanitize_key( wp_unslash( $_POST['fleet_outcome'] ) ) : '';
	if ( ! in_array( $outcome, array( 'available', 'custom' ), true ) ) {
		$outcome = $units <= 2 ? 'available' : 'custom';
	}

	$lines = array(
		'Name: ' . $name,
		'Email: ' . $email,
		'Company: ' . $company,
		'Country: ' . $countries[ $country ],
		'Estimated data need: ' . doppler_lidar_fleet_duration_label( $duration ),
		'Number of units: ' . $units,
		'Availability: ' . ( 'available' === $outcome ? 'Available for checked conditions' : 'Personalised solution' ),
	);

	$sent = wp_mail(
		get_option( 'admin_email' ),
		sprintf( '[Favionus] Fleet enquiry from %s', $name ),
		implode( "\n", $lines ),
		array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $email )
	);

	if ( ! $sent ) {
		wp_send_json_error( array( 'message' => __( 'We could not send your enquiry. Please try again.', 'doppler_lidar' ) ), 500 );
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_doppler_lidar_fleet', 'doppler_lidar_handle_fleet_request' );
add_action( 'wp_ajax_nopriv_doppler_lidar_fleet', 'doppler_lidar_handle_fleet_request' );

/**
 * Permalink of the research archive page.
 *
 * @return string
 */
function doppler_lidar_resources_url() {
	$page_id = (int) get_option( 'doppler_lidar_resources_page_id' );
	if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
		return get_permalink( $page_id );
	}

	$page = get_page_by_path( 'resources' );
	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}

	return home_url( '/resources/' );
}

/**
 * Estimated reading time for a post.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function doppler_lidar_reading_time( $post_id ) {
	$words   = str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ) );
	$minutes = max( 1, (int) ceil( $words / 200 ) );

	return sprintf(
		/* translators: %d: estimated minutes to read the article. */
		__( '%d min read', 'doppler_lidar' ),
		$minutes
	);
}

/**
 * Initials for the article byline.
 *
 * @param string $name Author display name.
 * @return string
 */
function doppler_lidar_author_initials( $name ) {
	$parts = preg_split( '/\s+/', trim( $name ) );
	$parts = array_values( array_filter( (array) $parts ) );
	if ( empty( $parts ) ) {
		return '';
	}
	$initials = mb_strtoupper( mb_substr( $parts[0], 0, 1 ) );
	if ( count( $parts ) > 1 ) {
		$initials .= mb_strtoupper( mb_substr( $parts[ count( $parts ) - 1 ], 0, 1 ) );
	}
	return $initials;
}

/**
 * Create the resources page and add it to the primary menu once.
 */
function doppler_lidar_bootstrap_resources() {
	if ( ! get_option( 'doppler_lidar_resources_page_id' ) ) {
		$existing = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'name'           => 'resources',
				'posts_per_page' => 1,
			)
		);

		if ( ! empty( $existing ) ) {
			$page_id = (int) $existing[0]->ID;
		} else {
			$inserted = wp_insert_post(
				array(
					'post_title'   => __( 'Technical Insights & Research Archive', 'doppler_lidar' ),
					'post_name'    => 'resources',
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_excerpt' => __( 'Empirical verification methodologies, sensor drift forensics, and operational campaign analyses. Calibrated for renewable lenders, met-ocean engineers, and bankable wind resource assessors.', 'doppler_lidar' ),
				),
				true
			);
			$page_id = is_wp_error( $inserted ) ? 0 : (int) $inserted;
		}

		if ( $page_id ) {
			update_post_meta( $page_id, '_wp_page_template', 'page-templates/resources.php' );
			update_option( 'doppler_lidar_resources_page_id', $page_id );
		}
	}

	if ( get_option( 'doppler_lidar_resources_menu_item' ) ) {
		return;
	}

	$locations = get_nav_menu_locations();
	if ( empty( $locations['menu-1'] ) ) {
		return;
	}

	$menu_id = (int) $locations['menu-1'];
	$url     = doppler_lidar_resources_url();
	$items   = wp_get_nav_menu_items( $menu_id );
	foreach ( (array) $items as $item ) {
		if ( $item instanceof WP_Post && false !== strpos( (string) $item->url, '/resources' ) ) {
			update_option( 'doppler_lidar_resources_menu_item', 1 );
			return;
		}
	}

	$added = wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'  => __( 'Research & Blog', 'doppler_lidar' ),
			'menu-item-url'    => $url,
			'menu-item-status' => 'publish',
			'menu-item-type'   => 'custom',
		)
	);

	if ( ! is_wp_error( $added ) ) {
		update_option( 'doppler_lidar_resources_menu_item', 1 );
	}
}
add_action( 'init', 'doppler_lidar_bootstrap_resources' );

/**
 * Full-bleed layout for the research archive.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function doppler_lidar_resources_body_class( $classes ) {
	if ( is_page_template( 'page-templates/resources.php' ) ) {
		$classes[] = 'wd-resources';
	}
	return $classes;
}
add_filter( 'body_class', 'doppler_lidar_resources_body_class' );

/**
 * Store a research-dispatch subscription request.
 */
function doppler_lidar_handle_dispatch_subscribe() {
	if ( ! isset( $_POST['doppler_lidar_dispatch_nonce'] ) ) {
		return;
	}

	$redirect = doppler_lidar_resources_url();

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['doppler_lidar_dispatch_nonce'] ) ), 'doppler_lidar_dispatch' ) ) {
		wp_safe_redirect( add_query_arg( 'dispatch', 'error', $redirect ) );
		exit;
	}

	if ( doppler_lidar_honeypot_filled() ) {
		wp_safe_redirect( add_query_arg( 'dispatch', 'sent', $redirect ) );
		exit;
	}

	$email = isset( $_POST['dispatch_email'] ) ? sanitize_email( wp_unslash( $_POST['dispatch_email'] ) ) : '';
	if ( ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'dispatch', 'error', $redirect ) );
		exit;
	}

	$sent = wp_mail(
		get_option( 'admin_email' ),
		'[Favionus] Research dispatch subscription',
		'Subscribe this address to the Favionus monthly engineering dispatch: ' . $email,
		array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $email )
	);

	wp_safe_redirect( add_query_arg( 'dispatch', $sent ? 'sent' : 'error', $redirect ) );
	exit;
}
add_action( 'template_redirect', 'doppler_lidar_handle_dispatch_subscribe' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

