<?php
namespace NewfoldLabs\WP\Module\TenWeb;

use NewfoldLabs\WP\ModuleLoader\Container;

/**
 * Bootstraps TenWeb module functionality.
 */
class TenWeb {

	/**
	 * Dependency injection container.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container The module container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;

		add_action( 'init', array( $this, 'load_textdomain' ), 100 );
	}

	/**
	 * Load the module text domain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'wp-module-10web',
			false,
			dirname( plugin_basename( NFD_TENWEB_DIR . '/bootstrap.php' ) ) . '/languages'
		);
	}
}
