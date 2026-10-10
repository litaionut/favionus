<?php
/**
 * Indexing fixes: noindex thin archives, rewrite stale staging-domain URLs.
 *
 * @package doppler_lidar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Old Bluehost staging host that still appears in Yoast indexables (archive canonicals).
 * The permanent fix is wp-admin › Yoast › Tools › "Optimise SEO data" (reset indexables);
 * this filter is a safety net until then.
 */
const DOPPLER_LIDAR_STALE_HOSTS = array( 'npx.mxs.mybluehost.me', 'www.npx.mxs.mybluehost.me' );

/**
 * Replace stale staging host with the live home host.
 *
 * @param mixed $url URL (string) or anything else (returned untouched).
 * @return mixed
 */
function doppler_lidar_fix_stale_host( $url ) {
	if ( ! is_string( $url ) || '' === $url ) {
		return $url;
	}
	$live = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	foreach ( DOPPLER_LIDAR_STALE_HOSTS as $stale ) {
		$url = str_replace( array( 'http://' . $stale, 'https://' . $stale ), 'https://' . $live, $url );
	}
	return $url;
}
add_filter( 'wpseo_canonical', 'doppler_lidar_fix_stale_host', 99 );
add_filter( 'wpseo_opengraph_url', 'doppler_lidar_fix_stale_host', 99 );
add_filter( 'get_canonical_url', 'doppler_lidar_fix_stale_host', 99 );

/**
 * Fix stale host inside Yoast schema graph strings.
 *
 * @param array $graph Schema graph.
 * @return array
 */
function doppler_lidar_fix_stale_host_schema( $graph ) {
	if ( ! is_array( $graph ) ) {
		return $graph;
	}
	array_walk_recursive(
		$graph,
		static function ( &$value ) {
			$value = doppler_lidar_fix_stale_host( $value );
		}
	);
	return $graph;
}
add_filter( 'wpseo_schema_graph', 'doppler_lidar_fix_stale_host_schema', 99 );

/**
 * Whether the current request is a thin archive that should not be indexed.
 * Author archives (single-author site), date archives, attachment pages,
 * and category/tag archives with fewer than 3 posts.
 */
function doppler_lidar_is_thin_archive() {
	if ( is_author() || is_date() || is_attachment() ) {
		return true;
	}
	if ( is_category() || is_tag() ) {
		$term = get_queried_object();
		return ( $term instanceof WP_Term ) && (int) $term->count < 3;
	}
	return false;
}

/**
 * Core robots (used when Yoast is inactive).
 *
 * @param array $robots Robots directives.
 * @return array
 */
function doppler_lidar_noindex_thin_archives( $robots ) {
	if ( doppler_lidar_is_thin_archive() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['index'] );
	}
	return $robots;
}
add_filter( 'wp_robots', 'doppler_lidar_noindex_thin_archives', 99 );

/**
 * Yoast robots string.
 *
 * @param string $robots Robots string.
 * @return string
 */
function doppler_lidar_yoast_noindex_thin_archives( $robots ) {
	return doppler_lidar_is_thin_archive() ? 'noindex, follow' : $robots;
}
add_filter( 'wpseo_robots', 'doppler_lidar_yoast_noindex_thin_archives', 99 );

/**
 * Keep author archives out of the Yoast sitemap.
 *
 * @param bool $exclude Whether to exclude.
 * @return bool
 */
add_filter( 'wpseo_sitemap_exclude_author', '__return_true' );
