<?php
/**
 * Plugin Name: Favionus SEO
 * Description: Technical SEO for Favionus — schema, Open Graph, HTTPS, thin-content noindex, llms.txt, seed pages.
 * Version: 1.1.0
 * Author: Favionus
 *
 * Drop-in must-use plugin. Survives theme switches. Complements Yoast; does not replace it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Favionus_SEO {

	const VERSION = '1.1.0';

	/** Default OG / social image (theme asset). Prefer a dedicated 1200×630 asset when available. */
	const DEFAULT_OG_IMAGE_PATH = '/wp-content/themes/doppler_lidar_v2/images/profiling-field.png';

	const META_DESC = 'Turnkey onshore wind LiDAR measurement campaigns — IEC-classified vertical profiling, remote monitoring, and quality-controlled bankable wind data for resource assessment.';

	const TITLE_HOME = 'Wind LiDAR Measurement Campaigns | Bankable Data | Favionus';

	public static function init(): void {
		add_action( 'template_redirect', array( __CLASS__, 'force_https' ), 0 );
		add_action( 'send_headers', array( __CLASS__, 'security_headers' ) );
		add_filter( 'wp_robots', array( __CLASS__, 'noindex_thin_content' ) );
		add_action( 'wp_head', array( __CLASS__, 'print_og_image_fallback' ), 20 );
		add_action( 'wpseo_add_opengraph_images', array( __CLASS__, 'yoast_add_og_image' ) );
		add_filter( 'wpseo_schema_graph', array( __CLASS__, 'enrich_yoast_schema' ), 20, 1 );
		add_filter( 'wpseo_metadesc', array( __CLASS__, 'improve_metadesc' ), 20, 1 );
		add_filter( 'wpseo_title', array( __CLASS__, 'improve_title' ), 20, 1 );
		add_filter( 'wpseo_opengraph_image', array( __CLASS__, 'fallback_og_image' ), 20, 1 );
		add_filter( 'wpseo_twitter_image', array( __CLASS__, 'fallback_og_image' ), 20, 1 );
		add_action( 'init', array( __CLASS__, 'register_llms_txt' ) );
		add_action( 'init', array( __CLASS__, 'register_ai_txt' ) );
		add_action( 'init', array( __CLASS__, 'maybe_seed_pages' ), 20 );
		add_filter( 'robots_txt', array( __CLASS__, 'augment_robots_txt' ), 20, 2 );
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrites' ), 99 );
	}

	public static function og_image_url(): string {
		return home_url( self::DEFAULT_OG_IMAGE_PATH );
	}

	/** Redirect HTTP → HTTPS. */
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
	 * @param array<string,bool> $robots Robots directives.
	 * @return array<string,bool>
	 */
	public static function noindex_thin_content( array $robots ): array {
		if ( is_singular( 'post' ) ) {
			$post = get_queried_object();
			if ( $post instanceof WP_Post && 'hello-world' === $post->post_name ) {
				$robots['noindex']  = true;
				$robots['nofollow'] = true;
				unset( $robots['index'], $robots['follow'] );
			}
		}
		if ( is_category( 'uncategorized' ) ) {
			$robots['noindex'] = true;
			unset( $robots['index'] );
		}
		return $robots;
	}

	/**
	 * Ensure Yoast emits an OG image even when no featured image is set.
	 *
	 * @param object $image_container Yoast image container.
	 */
	public static function yoast_add_og_image( $image_container ): void {
		if ( ! is_object( $image_container ) || ! method_exists( $image_container, 'add_image_by_url' ) ) {
			return;
		}
		$image_container->add_image_by_url( self::og_image_url() );
	}

	/** Hard fallback if Yoast still omits og:image. */
	public static function print_og_image_fallback(): void {
		echo "\n<!-- Favionus SEO " . esc_html( self::VERSION ) . " -->\n";
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			$url = esc_url( self::og_image_url() );
			echo '<meta property="og:image" content="' . $url . '" />' . "\n";
			echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
			echo '<meta name="twitter:image" content="' . $url . '" />' . "\n";
			return;
		}
		// Detect empty Yoast social image on front page via late check is unreliable;
		// wpseo_add_opengraph_images handles the normal path. Extra meta only if filter returned empty earlier.
	}

	/**
	 * @param string $desc Current meta description.
	 */
	public static function improve_metadesc( $desc ): string {
		$desc = is_string( $desc ) ? trim( $desc ) : '';
		if ( ( $desc === '' || strtolower( $desc ) === 'measure the wind' ) && ( is_front_page() || is_home() ) ) {
			return self::META_DESC;
		}
		return $desc;
	}

	/**
	 * @param string $title Current title.
	 */
	public static function improve_title( $title ): string {
		$title = is_string( $title ) ? trim( $title ) : '';
		if ( ( is_front_page() || is_home() ) && ( $title === '' || stripos( $title, 'measure the wind' ) !== false ) ) {
			return self::TITLE_HOME;
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
		return self::og_image_url();
	}

	/**
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
					'url'   => self::og_image_url(),
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
			);
		}

		$graph[] = array(
			'@type'            => 'ProfessionalService',
			'@id'              => $service_id,
			'name'             => 'Turnkey wind LiDAR measurement campaigns',
			'url'              => home_url( '/' ),
			'provider'         => array( '@id' => $org_id ),
			'description'      => self::META_DESC,
			'serviceType'      => array(
				'Wind resource assessment',
				'Vertical profiling LiDAR campaign',
				'Met mast complement measurement',
				'Wind farm repowering measurement',
			),
			'areaServed'       => 'Europe',
			'availableChannel' => array(
				'@type'      => 'ServiceChannel',
				'serviceUrl' => home_url( '/#contact' ),
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
		$path = ABSPATH . 'llms.txt';
		if ( is_readable( $path ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain text file.
			echo file_get_contents( $path );
			exit;
		}
		echo "# Favionus\n";
		echo "> Turnkey onshore wind LiDAR measurement campaigns. Bankable, quality-controlled wind data for developers, lenders and engineers.\n\n";
		echo "Favionus plans, deploys, monitors and delivers IEC-classified vertical profiling LiDAR campaigns for wind resource assessment, met mast complement, repowering and site investigation.\n\n";
		echo "## Site\n";
		echo '- Home: ' . home_url( '/' ) . "\n";
		echo '- Contact: ' . home_url( '/#contact' ) . "\n";
		echo "- Email: contact@favionus.com\n";
		echo "- Location: Bucharest, Romania — campaigns across Europe\n\n";
		echo "## Services\n";
		echo '- ' . home_url( '/wind-lidar-measurement-campaigns/' ) . "\n";
		echo '- ' . home_url( '/wind-resource-assessment/' ) . "\n";
		echo '- ' . home_url( '/met-mast-complement/' ) . "\n";
		echo '- ' . home_url( '/repowering-wind-measurement/' ) . "\n";
		exit;
	}

	public static function serve_ai_txt(): void {
		if ( ! get_query_var( 'favionus_ai' ) ) {
			return;
		}
		header( 'Content-Type: text/plain; charset=utf-8' );
		$path = ABSPATH . 'ai.txt';
		if ( is_readable( $path ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo file_get_contents( $path );
			exit;
		}
		echo "# ai.txt for favionus.com\n";
		echo "User-Agent: *\nAllow: /\n";
		echo "Contact: contact@favionus.com\n";
		echo 'Sitemap: ' . home_url( '/sitemap_index.xml' ) . "\n";
		echo 'llms: ' . home_url( '/llms.txt' ) . "\n";
		exit;
	}

	/**
	 * Create service + legal pages once from content/pages/*.md drafts.
	 */
	public static function maybe_seed_pages(): void {
		if ( get_option( 'favionus_seo_pages_seeded' ) === self::VERSION ) {
			return;
		}
		if ( ! function_exists( 'wp_insert_post' ) ) {
			return;
		}

		$pages = array(
			'wind-lidar-measurement-campaigns' => array(
				'title' => 'Wind LiDAR Measurement Campaigns',
				'file'  => 'wind-lidar-measurement-campaigns.md',
			),
			'wind-resource-assessment'         => array(
				'title' => 'Wind Resource Assessment with LiDAR',
				'file'  => 'wind-resource-assessment.md',
			),
			'met-mast-complement'              => array(
				'title' => 'Met Mast Complement',
				'file'  => 'met-mast-complement.md',
			),
			'repowering-wind-measurement'      => array(
				'title' => 'Repowering Wind Measurement',
				'file'  => 'repowering-wind-measurement.md',
			),
			'privacy-policy'                   => array(
				'title' => 'Privacy Policy',
				'file'  => 'privacy-policy.md',
			),
			'terms-of-use'                     => array(
				'title' => 'Terms of Use',
				'file'  => 'terms-of-use.md',
			),
		);

		$base = trailingslashit( ABSPATH ) . 'content/pages/';
		foreach ( $pages as $slug => $meta ) {
			$existing = get_page_by_path( $slug );
			if ( $existing instanceof WP_Post ) {
				continue;
			}
			$file = $base . $meta['file'];
			if ( ! is_readable( $file ) ) {
				continue;
			}
			$markdown = file_get_contents( $file );
			$html     = self::markdown_to_html( $markdown );
			wp_insert_post(
				array(
					'post_title'   => $meta['title'],
					'post_name'    => $slug,
					'post_content' => $html,
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_author'  => 1,
				),
				true
			);
		}

		// Trash Hello World if still present.
		$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
		if ( $hello instanceof WP_Post ) {
			wp_trash_post( $hello->ID );
		}

		update_option( 'favionus_seo_pages_seeded', self::VERSION, true );
	}

	/**
	 * Minimal Markdown → HTML for our page drafts (headings, lists, bold, links, paragraphs).
	 */
	public static function markdown_to_html( string $md ): string {
		$lines  = preg_split( '/\R/', $md );
		$html   = array();
		$in_ul  = false;
		$in_ol  = false;
		$para   = array();

		$flush_para = static function () use ( &$para, &$html ) {
			if ( $para ) {
				$html[] = '<p>' . implode( ' ', $para ) . '</p>';
				$para   = array();
			}
		};
		$close_lists = static function () use ( &$in_ul, &$in_ol, &$html ) {
			if ( $in_ul ) {
				$html[] = '</ul>';
				$in_ul  = false;
			}
			if ( $in_ol ) {
				$html[] = '</ol>';
				$in_ol  = false;
			}
		};

		foreach ( $lines as $line ) {
			$trim = trim( $line );
			if ( $trim === '' ) {
				$flush_para();
				$close_lists();
				continue;
			}
			if ( preg_match( '/^(#{1,3})\s+(.+)$/', $trim, $m ) ) {
				$flush_para();
				$close_lists();
				$level  = strlen( $m[1] );
				$html[] = '<h' . $level . '>' . self::inline_md( $m[2] ) . '</h' . $level . '>';
				continue;
			}
			if ( preg_match( '/^[-*]\s+(.+)$/', $trim, $m ) ) {
				$flush_para();
				if ( $in_ul ) {
					$html[] = '</ol>';
					$in_ol  = false;
				}
				if ( ! $in_ul ) {
					$html[] = '<ul>';
					$in_ul  = true;
				}
				$html[] = '<li>' . self::inline_md( $m[1] ) . '</li>';
				continue;
			}
			if ( preg_match( '/^\d+\.\s+(.+)$/', $trim, $m ) ) {
				$flush_para();
				if ( $in_ul ) {
					$html[] = '</ul>';
					$in_ul  = false;
				}
				if ( ! $in_ol ) {
					$html[] = '<ol>';
					$in_ol  = true;
				}
				$html[] = '<li>' . self::inline_md( $m[1] ) . '</li>';
				continue;
			}
			$close_lists();
			$para[] = self::inline_md( $trim );
		}
		$flush_para();
		$close_lists();
		return implode( "\n", $html );
	}

	private static function inline_md( string $text ): string {
		$text = esc_html( $text );
		$text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
		$text = preg_replace( '/\[(.+?)\]\((\/[^)]+|https?:\/\/[^)]+)\)/', '<a href="$2">$1</a>', $text );
		return $text;
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
		$extra .= '# See also: ' . home_url( '/llms.txt' ) . "\n";
		return $output . $extra;
	}

	public static function maybe_flush_rewrites(): void {
		$ver = get_option( 'favionus_seo_rewrite_ver' );
		if ( $ver !== self::VERSION ) {
			flush_rewrite_rules( false );
			update_option( 'favionus_seo_rewrite_ver', self::VERSION, true );
		}
	}
}

Favionus_SEO::init();
