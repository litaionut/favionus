<?php
/**
 * Plugin Name: Favionus SEO
 * Description: Technical SEO for Favionus — schema, Open Graph defaults, HTTPS, thin-content noindex, llms.txt.
 * Version: 1.0.0
 * Author: Favionus
 *
 * Drop-in must-use plugin. Survives theme switches. Complements Yoast; does not replace it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Favionus_SEO {

	const VERSION = '1.0.0';

	/** Default OG / social image (theme asset). Prefer a dedicated 1200×630 asset when available. */
	const DEFAULT_OG_IMAGE_PATH = '/wp-content/themes/doppler_lidar_v2/images/profiling-field.png';

	const META_DESC = 'Turnkey onshore wind LiDAR measurement campaigns — IEC-classified vertical profiling, remote monitoring, and quality-controlled bankable wind data for resource assessment.';

	const TITLE_HOME = 'Wind LiDAR Measurement Campaigns | Bankable Data | Favionus';

	public static function init(): void {
		add_action( 'template_redirect', array( __CLASS__, 'force_https' ), 0 );
		add_action( 'send_headers', array( __CLASS__, 'security_headers' ) );
		add_filter( 'wp_robots', array( __CLASS__, 'noindex_thin_content' ) );
		add_action( 'wp_head', array( __CLASS__, 'fallback_social_meta' ), 5 );
		add_filter( 'wpseo_schema_graph', array( __CLASS__, 'enrich_yoast_schema' ), 20, 1 );
		add_filter( 'wpseo_metadesc', array( __CLASS__, 'improve_metadesc' ), 20, 1 );
		add_filter( 'wpseo_title', array( __CLASS__, 'improve_title' ), 20, 1 );
		add_filter( 'wpseo_opengraph_image', array( __CLASS__, 'fallback_og_image' ), 20, 1 );
		add_filter( 'wpseo_twitter_image', array( __CLASS__, 'fallback_og_image' ), 20, 1 );
		add_action( 'init', array( __CLASS__, 'register_llms_txt' ) );
		add_action( 'init', array( __CLASS__, 'register_ai_txt' ) );
		add_filter( 'robots_txt', array( __CLASS__, 'augment_robots_txt' ), 20, 2 );
	}

	/** Redirect HTTP → HTTPS (fixes bare http:// serving 200). */
	public static function force_https(): void {
		if ( is_ssl() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}
		if ( empty( $_SERVER['HTTP_HOST'] ) || empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$host = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
		$uri  = wp_unslash( $_SERVER['REQUEST_URI'] );
		wp_redirect( 'https://' . $host . $uri, 301 );
		exit;
	}

	public static function security_headers(): void {
		if ( headers_sent() ) {
			return;
		}
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'X-Frame-Options: SAMEORIGIN' );
	}

	/**
	 * Noindex default WP junk that leaks into the sitemap.
	 *
	 * @param array<string,bool> $robots Robots directives.
	 * @return array<string,bool>
	 */
	public static function noindex_thin_content( array $robots ): array {
		if ( is_singular( 'post' ) ) {
			$post = get_queried_object();
			if ( $post instanceof WP_Post ) {
				$slug = $post->post_name;
				if ( in_array( $slug, array( 'hello-world' ), true ) ) {
					$robots['noindex']  = true;
					$robots['nofollow'] = true;
					unset( $robots['index'], $robots['follow'] );
				}
			}
		}
		if ( is_category( 'uncategorized' ) ) {
			$robots['noindex'] = true;
			unset( $robots['index'] );
		}
		return $robots;
	}

	/** Fallback OG/Twitter tags when Yoast has no image / weak social set. */
	public static function fallback_social_meta(): void {
		if ( ! is_front_page() && ! is_home() ) {
			return;
		}
		$has_og = false;
		// Yoast prints og tags later; we only add image if filter returns empty and we detect absence at runtime is hard — use filters instead.
		echo "\n<!-- Favionus SEO " . esc_html( self::VERSION ) . " -->\n";
	}

	/**
	 * @param string $desc Current meta description.
	 */
	public static function improve_metadesc( $desc ): string {
		$desc = is_string( $desc ) ? trim( $desc ) : '';
		if ( $desc === '' || strtolower( $desc ) === 'measure the wind' ) {
			if ( is_front_page() || is_home() ) {
				return self::META_DESC;
			}
		}
		return $desc;
	}

	/**
	 * @param string $title Current title.
	 */
	public static function improve_title( $title ): string {
		$title = is_string( $title ) ? trim( $title ) : '';
		if ( is_front_page() || is_home() ) {
			if ( $title === '' || stripos( $title, 'measure the wind' ) !== false ) {
				return self::TITLE_HOME;
			}
		}
		return $title;
	}

	/**
	 * @param string|false $img Current image URL.
	 * @return string
	 */
	public static function fallback_og_image( $img ) {
		if ( is_string( $img ) && $img !== '' ) {
			return $img;
		}
		return home_url( self::DEFAULT_OG_IMAGE_PATH );
	}

	/**
	 * Add Organization + ProfessionalService nodes to Yoast graph.
	 *
	 * @param array<int,array<string,mixed>> $graph Schema graph.
	 * @return array<int,array<string,mixed>>
	 */
	public static function enrich_yoast_schema( array $graph ): array {
		$org_id     = home_url( '/#organization' );
		$service_id = home_url( '/#service' );

		$has_org = false;
		foreach ( $graph as $node ) {
			$types = isset( $node['@type'] ) ? (array) $node['@type'] : array();
			if ( in_array( 'Organization', $types, true ) ) {
				$has_org = true;
				break;
			}
		}

		if ( ! $has_org ) {
			$graph[] = array(
				'@type'       => 'Organization',
				'@id'         => $org_id,
				'name'        => 'Favionus',
				'url'         => home_url( '/' ),
				'logo'        => array(
					'@type' => 'ImageObject',
					'url'   => home_url( self::DEFAULT_OG_IMAGE_PATH ),
				),
				'description' => self::META_DESC,
				'email'       => 'contact@favionus.com',
				'address'     => array(
					'@type'           => 'PostalAddress',
					'addressLocality' => 'Bucharest',
					'addressCountry'  => 'RO',
				),
				'areaServed'  => array(
					'@type' => 'Place',
					'name'  => 'Europe',
				),
				'sameAs'      => array(),
			);
		}

		$graph[] = array(
			'@type'           => 'ProfessionalService',
			'@id'             => $service_id,
			'name'            => 'Turnkey wind LiDAR measurement campaigns',
			'url'             => home_url( '/' ),
			'provider'        => array( '@id' => $org_id ),
			'description'     => self::META_DESC,
			'serviceType'     => array(
				'Wind resource assessment',
				'Vertical profiling LiDAR campaign',
				'Met mast complement measurement',
				'Wind farm repowering measurement',
			),
			'areaServed'      => 'Europe',
			'availableChannel'=> array(
				'@type'       => 'ServiceChannel',
				'serviceUrl'  => home_url( '/#contact' ),
			),
		);

		return $graph;
	}

	public static function register_llms_txt(): void {
		add_rewrite_rule( '^llms\.txt$', 'index.php?favionus_llms=1', 'top' );
		add_rewrite_tag( '%favionus_llms%', '1' );
		add_action( 'template_redirect', array( __CLASS__, 'serve_llms_txt' ) );
	}

	public static function register_ai_txt(): void {
		add_rewrite_rule( '^ai\.txt$', 'index.php?favionus_ai=1', 'top' );
		add_rewrite_tag( '%favionus_ai%', '1' );
		add_action( 'template_redirect', array( __CLASS__, 'serve_ai_txt' ) );
	}

	public static function serve_llms_txt(): void {
		if ( ! get_query_var( 'favionus_llms' ) ) {
			return;
		}
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: all' );
		echo "# Favionus\n";
		echo "> Turnkey onshore wind LiDAR measurement campaigns. Bankable, quality-controlled wind data for developers, lenders and engineers.\n\n";
		echo "Favionus plans, deploys, monitors and delivers IEC-classified vertical profiling LiDAR campaigns for wind resource assessment, met mast complement, repowering and site investigation.\n\n";
		echo "## Site\n";
		echo "- Home: " . home_url( '/' ) . "\n";
		echo "- Contact: " . home_url( '/#contact' ) . "\n";
		echo "- Email: contact@favionus.com\n";
		echo "- Location: Bucharest, Romania — campaigns across Europe\n\n";
		echo "## Services\n";
		echo "- Wind resource assessment LiDAR campaigns\n";
		echo "- Met mast complement / rotor-swept height measurement\n";
		echo "- Repowering wind measurement\n";
		echo "- Prospective site investigation\n\n";
		echo "## Standards & keywords\n";
		echo "IEC-classified LiDAR, vertical profiling, bankable wind data, remote monitoring, quality-controlled datasets, energy yield assessment support.\n";
		exit;
	}

	public static function serve_ai_txt(): void {
		if ( ! get_query_var( 'favionus_ai' ) ) {
			return;
		}
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo "# ai.txt for favionus.com\n";
		echo "User-Agent: *\n";
		echo "Allow: /\n";
		echo "Contact: contact@favionus.com\n";
		echo "Sitemap: " . home_url( '/sitemap_index.xml' ) . "\n";
		echo "llms: " . home_url( '/llms.txt' ) . "\n";
		exit;
	}

	/**
	 * @param string $output Robots.txt body.
	 * @param bool   $public Blog public flag.
	 */
	public static function augment_robots_txt( string $output, bool $public ): string {
		if ( ! $public ) {
			return $output;
		}
		$extra  = "\n# Favionus SEO\n";
		$extra .= "User-agent: GPTBot\nAllow: /\n\n";
		$extra .= "User-agent: ChatGPT-User\nAllow: /\n\n";
		$extra .= "User-agent: OAI-SearchBot\nAllow: /\n\n";
		$extra .= "User-agent: Google-Extended\nAllow: /\n\n";
		$extra .= 'Sitemap: ' . home_url( '/sitemap_index.xml' ) . "\n";
		if ( stripos( $output, 'llms.txt' ) === false ) {
			$extra .= '# See also: ' . home_url( '/llms.txt' ) . "\n";
		}
		return $output . $extra;
	}
}

Favionus_SEO::init();

// MU-plugins have no activation hook — flush rewrites once after deploy.
add_action(
	'init',
	static function () {
		$ver = get_option( 'favionus_seo_rewrite_ver' );
		if ( $ver !== Favionus_SEO::VERSION ) {
			flush_rewrite_rules( false );
			update_option( 'favionus_seo_rewrite_ver', Favionus_SEO::VERSION, true );
		}
	},
	99
);
