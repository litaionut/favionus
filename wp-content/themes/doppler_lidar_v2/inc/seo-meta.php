<?php
/**
 * Default SEO titles/meta descriptions, Organization/LocalBusiness/Service schema and og:image fallback.
 *
 * Titles/descriptions here are DEFAULTS: a custom value entered in the Yoast box
 * in wp-admin always wins. Values come from /workspace/seo-romania-plan.md.
 *
 * @package doppler_lidar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Path => array( title, description ).
 */
function doppler_lidar_seo_defaults() {
	return array(
		'/'                                       => array( 'Turnkey LiDAR Wind Measurement Campaigns – Favionus', 'Turnkey wind LiDAR measurement campaigns for onshore wind projects in Romania and Europe: deployment, 24/7 monitoring, quality-controlled data.' ),
		'/ro/'                                    => array( 'Campanii de măsurare a vântului cu LiDAR – Favionus', 'Campanii LiDAR la cheie pentru măsurarea vântului în proiecte eoliene onshore din România: instalare, monitorizare 24/7 și date verificate.' ),
		'/wind-lidar-measurement-campaigns/'      => array( 'Wind Measurement Campaigns in Romania with LiDAR', 'Turnkey onshore wind LiDAR campaigns and wind LiDAR rental: LiDAR units from leading manufacturers, autonomous power, monitoring and QC\'d data.' ),
		'/ro/campanii-masurare-vant-lidar/'       => array( 'Campanie de măsurare a vântului cu LiDAR – Favionus', 'Campanii de măsurare a vântului și închiriere LiDAR pentru parcuri eoliene din România: LiDAR cu profil vertical, alimentare autonomă, date verificate.' ),
		'/wind-resource-assessment/'              => array( 'Wind Resource Assessment Measurements in Romania', 'On-site LiDAR wind measurements for wind resource assessment of onshore wind farms in Romania: campaign design, heights, duration and QC\'d data.' ),
		'/met-mast-complement/'                   => array( 'Met Mast Complement: LiDAR for Rotor-Height Wind Data', 'Complement your met mast with vertical profiling LiDAR to measure across the rotor-swept height and reduce shear extrapolation uncertainty.' ),
		'/repowering-wind-measurement/'           => array( 'Wind Measurement for Repowering Projects – Favionus', 'LiDAR wind measurements at modern hub and rotor heights for repowering onshore wind farms in Romania and Europe.' ),
		'/lidar-or-a-met-mast/'                   => array( 'LiDAR or Met Mast? How to Choose for Your Wind Project', 'Who decides, site complexity, heights, permits and bankability: a practical guide to choosing LiDAR, a met mast or both for your wind project.' ),
		'/ro/blog/lidar-sau-turn-de-masura/'      => array( 'LiDAR sau turn de măsură? Cum alegeți pentru proiect', 'Cine decide, complexitatea terenului, înălțimi, autorizații și bancabilitate: ghid practic pentru alegerea între LiDAR, turn de măsură sau ambele.' ),
		'/ro/blog/cum-se-desfasoara-campanie-masurare-vant/' => array( 'Cum se desfășoară o campanie de măsurare a vântului', 'De la obiective și înălțimi la instalare, monitorizare și livrarea datelor: etapele unei campanii LiDAR de măsurare a vântului pentru un parc eolian.' ),
		'/resources/'                             => array( 'Wind LiDAR & Wind Resource Assessment Guides – Favionus', 'Practical guides on wind LiDAR campaigns, data quality and wind resource assessment for onshore wind developers, lenders and engineers.' ),
		'/privacy-policy/'                        => array( 'Privacy Policy – Favionus', 'How Favionus S.R.L. collects and uses personal data from website enquiries, and your GDPR rights.' ),
		'/terms-of-use/'                          => array( 'Terms of Use – Favionus', 'Terms for using favionus.com: information only, offers by written proposal, Romanian law.' ),
	);
}

/**
 * Current request path with trailing slash.
 */
function doppler_lidar_seo_path() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '/';
	return trailingslashit( '/' . ltrim( $uri, '/' ) );
}

/**
 * Default for the current page, or null. Index: 0 = title, 1 = description.
 *
 * @param int $index Field index.
 */
function doppler_lidar_seo_default( $index ) {
	if ( is_search() || is_404() || is_paged() ) {
		return null;
	}
	$map  = doppler_lidar_seo_defaults();
	$path = doppler_lidar_seo_path();
	return isset( $map[ $path ][ $index ] ) ? $map[ $path ][ $index ] : null;
}

/**
 * Whether the editor set a custom Yoast value for this field on the queried post.
 *
 * @param string $key Meta key suffix ('title' or 'metadesc').
 */
function doppler_lidar_has_custom_yoast( $key ) {
	$id = get_queried_object_id();
	if ( ! $id || ! is_singular() ) {
		// Front page showing posts: Yoast stores it in options.
		$opts = get_option( 'wpseo_titles' );
		$opt  = 'title' === $key ? 'title-home-wpseo' : 'metadesc-home-wpseo';
		$val  = is_array( $opts ) && isset( $opts[ $opt ] ) ? trim( (string) $opts[ $opt ] ) : '';
		// The stock home title template ("%%sitename%% %%page%% %%sep%% %%sitedesc%%") is not custom.
		return '' !== $val && false === strpos( $val, '%%sitedesc%%' );
	}
	return '' !== trim( (string) get_post_meta( $id, '_yoast_wpseo_' . $key, true ) );
}

/**
 * @param string $title Title.
 * @return string
 */
function doppler_lidar_seo_title( $title ) {
	$default = doppler_lidar_seo_default( 0 );
	return ( $default && ! doppler_lidar_has_custom_yoast( 'title' ) ) ? $default : $title;
}
add_filter( 'wpseo_title', 'doppler_lidar_seo_title', 20 );
add_filter( 'wpseo_opengraph_title', 'doppler_lidar_seo_title', 20 );
add_filter( 'pre_get_document_title', static function ( $title ) {
	return defined( 'WPSEO_VERSION' ) ? $title : ( doppler_lidar_seo_default( 0 ) ? doppler_lidar_seo_default( 0 ) : $title );
}, 20 );

/**
 * @param string $desc Description.
 * @return string
 */
function doppler_lidar_seo_metadesc( $desc ) {
	$default = doppler_lidar_seo_default( 1 );
	return ( $default && ! doppler_lidar_has_custom_yoast( 'metadesc' ) ) ? $default : $desc;
}
add_filter( 'wpseo_metadesc', 'doppler_lidar_seo_metadesc', 20 );
add_filter( 'wpseo_opengraph_desc', 'doppler_lidar_seo_metadesc', 20 );

/**
 * Default social image (theme asset) when a page has none.
 */
function doppler_lidar_default_og_image() {
	return get_template_directory_uri() . '/images/profiling-field.png';
}

/**
 * @param object $container Yoast image container.
 */
function doppler_lidar_add_og_image( $container ) {
	if ( is_object( $container ) && method_exists( $container, 'has_images' ) && ! $container->has_images() && method_exists( $container, 'add_image_by_url' ) ) {
		$container->add_image_by_url( doppler_lidar_default_og_image() );
	}
}
add_action( 'wpseo_add_opengraph_images', 'doppler_lidar_add_og_image' );

/**
 * Organization + LocalBusiness (ProfessionalService) and Service nodes in the Yoast graph.
 *
 * Address: only locality/country are known. TODO [confirm]: street address, postal code,
 * ONRC/CUI (vatID), LinkedIn URL (sameAs), telephone. Do not add until confirmed.
 *
 * @param array $graph Graph.
 * @return array
 */
function doppler_lidar_schema_graph( $graph ) {
	if ( ! is_array( $graph ) ) {
		return $graph;
	}
	$org_id = home_url( '/#organization' );
	$is_ro  = function_exists( 'doppler_lidar_is_ro' ) && doppler_lidar_is_ro();

	$org = array(
		'@type'       => array( 'Organization', 'ProfessionalService' ),
		'@id'         => $org_id,
		'name'        => 'Favionus',
		'legalName'   => 'Favionus S.R.L.',
		'url'         => home_url( '/' ),
		'email'       => 'contact@favionus.com',
		'image'       => doppler_lidar_default_og_image(),
		'logo'        => array(
			'@type' => 'ImageObject',
			'url'   => get_site_icon_url( 512 ) ? get_site_icon_url( 512 ) : doppler_lidar_default_og_image(),
		),
		'description' => 'Turnkey wind LiDAR measurement campaigns and wind LiDAR rental for onshore wind projects in Romania and Europe.',
		'address'     => array(
			'@type'           => 'PostalAddress',
			'addressLocality' => 'București',
			'addressCountry'  => 'RO',
		),
		'areaServed'  => array(
			array( '@type' => 'Country', 'name' => 'Romania' ),
			array( '@type' => 'Place', 'name' => 'Europe' ),
		),
		'knowsLanguage' => array( 'en', 'ro' ),
	);

	// Replace Yoast's Organization node if present, otherwise add ours.
	$found = false;
	foreach ( $graph as $i => $node ) {
		$types = isset( $node['@type'] ) ? (array) $node['@type'] : array();
		if ( in_array( 'Organization', $types, true ) ) {
			$graph[ $i ] = array_merge( $node, $org, array( '@id' => isset( $node['@id'] ) ? $node['@id'] : $org_id ) );
			$org_id      = $graph[ $i ]['@id'];
			$found       = true;
		}
		if ( in_array( 'WebSite', $types, true ) ) {
			$graph[ $i ]['publisher'] = array( '@id' => $org_id );
			if ( $is_ro ) {
				$graph[ $i ]['inLanguage'] = 'ro-RO';
			}
		}
	}
	if ( ! $found ) {
		$graph[] = $org;
	}

	$path     = function_exists( 'doppler_lidar_seo_path' ) ? doppler_lidar_seo_path() : '';
	$services = array(
		'/wind-lidar-measurement-campaigns/' => array( 'Wind measurement campaign', 'Wind LiDAR measurement campaigns and wind LiDAR rental', 'en' ),
		'/ro/campanii-masurare-vant-lidar/'  => array( 'Campanie de măsurare a vântului', 'Campanii de măsurare a vântului cu LiDAR și închiriere LiDAR pentru vânt', 'ro' ),
		'/wind-resource-assessment/'         => array( 'Wind resource assessment measurements', 'LiDAR wind measurements for wind resource assessment', 'en' ),
		'/met-mast-complement/'              => array( 'Met mast complement', 'Vertical profiling LiDAR alongside an existing met mast', 'en' ),
		'/repowering-wind-measurement/'      => array( 'Repowering wind measurement', 'LiDAR wind measurements for repowering projects', 'en' ),
	);
	if ( isset( $services[ $path ] ) ) {
		$graph[] = array(
			'@type'       => 'Service',
			'@id'         => home_url( $path ) . '#service',
			'serviceType' => $services[ $path ][0],
			'name'        => $services[ $path ][1],
			'url'         => home_url( $path ),
			'inLanguage'  => $services[ $path ][2],
			'provider'    => array( '@id' => $org_id ),
			'areaServed'  => array(
				array( '@type' => 'Country', 'name' => 'Romania' ),
				array( '@type' => 'Place', 'name' => 'Europe' ),
			),
		);
	}
	return $graph;
}
add_filter( 'wpseo_schema_graph', 'doppler_lidar_schema_graph', 30 );
