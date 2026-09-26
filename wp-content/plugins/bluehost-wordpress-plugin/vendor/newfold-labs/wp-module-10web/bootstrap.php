<?php
/**
 * TenWeb module for integrating 10Web features into the plugin.
 *
 * Note: Class namespace uses "TenWeb" (PSR-4 compliance); brand references use "10Web".
 *
 * @package NewfoldLabs\WP\Module\TenWeb
 */

use NewfoldLabs\WP\ModuleLoader\Container;
use NewfoldLabs\WP\Module\TenWeb\TenWeb;
use NewfoldLabs\WP\Module\TenWeb\TenWebAdminRestrictionsFeature;
use NewfoldLabs\WP\Module\TenWeb\TenWebEditorSupportFeature;
use function NewfoldLabs\WP\ModuleLoader\register;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! defined( 'NFD_TENWEB_MODULE_VERSION' ) ) {
	define( 'NFD_TENWEB_MODULE_VERSION', '1.0.4' );
}

if ( function_exists( 'add_filter' ) ) {
	add_filter(
		'newfold/features/filter/register',
		function ( $features ) {
			return array_merge(
				$features,
				array(
					TenWebAdminRestrictionsFeature::class,
					TenWebEditorSupportFeature::class,
				)
			);
		}
	);
}

if ( function_exists( 'add_action' ) ) {

	add_action(
		'plugins_loaded',
		function () {
			register(
				array(
					'name'     => 'tenweb',
					'label'    => __( '10Web', 'wp-module-10web' ),
					'callback' => function ( Container $container ) {
						if ( ! defined( 'NFD_TENWEB_DIR' ) ) {
							define( 'NFD_TENWEB_DIR', __DIR__ );
						}
						if ( ! defined( 'NFD_TENWEB_BUILD_DIR' ) ) {
							define( 'NFD_TENWEB_BUILD_DIR', __DIR__ . '/build' );
						}
						if ( ! defined( 'NFD_TENWEB_BUILD_URL' ) ) {
							define(
								'NFD_TENWEB_BUILD_URL',
								$container->plugin()->url . 'vendor/newfold-labs/wp-module-10web/build'
							);
						}

						return new TenWeb( $container );
					},
					'isActive' => true,
					'isHidden' => true,
				)
			);
		}
	);

}
