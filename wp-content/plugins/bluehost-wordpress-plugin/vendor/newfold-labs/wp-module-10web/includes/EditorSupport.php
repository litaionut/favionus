<?php
namespace NewfoldLabs\WP\Module\TenWeb;

/**
 * Loads editor support assets on the WVC editor admin screen.
 */
class EditorSupport {

	/**
	 * Script handle for the editor support bundle.
	 *
	 * @var string
	 */
	public static $handle = 'nfd-tenweb-editor-support';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// The WVC editor exits during admin_init before admin_enqueue_scripts runs.
		add_action( 'admin_init', array( $this, 'maybe_enqueue_editor_scripts' ), 9 );
		add_action( 'load-post.php', array( $this, 'maybe_enqueue_editor_scripts' ), 9 );
	}

	/**
	 * Enqueue PostHog session replay on the WVC editor admin screen.
	 *
	 * @return void
	 */
	public function maybe_enqueue_editor_scripts() {
		if ( ! $this->is_wvc_editor_request() ) {
			return;
		}

		$asset_file = NFD_TENWEB_BUILD_DIR . '/editor-support/index.asset.php';
		if ( ! is_readable( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			self::$handle,
			NFD_TENWEB_BUILD_URL . '/editor-support/index.js',
			$asset['dependencies'],
			$asset['version'],
			false
		);
	}

	/**
	 * Whether the current request is loading the WVC editor.
	 *
	 * @return bool
	 */
	public function is_wvc_editor_request() {
		if ( ! is_admin() ) {
			return false;
		}

		global $pagenow;

		if ( 'admin.php' === $pagenow ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page slug check only.
			return isset( $_GET['page'] ) && 'wvc-editor' === sanitize_text_field( wp_unslash( $_GET['page'] ) );
		}

		if ( 'post.php' === $pagenow ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- editor action check only.
			return isset( $_GET['action'] ) && 'wvc-editor' === sanitize_text_field( wp_unslash( $_GET['action'] ) );
		}

		return false;
	}
}
