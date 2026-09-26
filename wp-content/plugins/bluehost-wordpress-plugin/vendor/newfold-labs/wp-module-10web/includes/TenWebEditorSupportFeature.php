<?php
namespace NewfoldLabs\WP\Module\TenWeb;

/**
 * Feature flag for TenWeb editor PostHog session replay.
 */
class TenWebEditorSupportFeature extends \NewfoldLabs\WP\Module\Features\Feature {

	/**
	 * The feature name.
	 *
	 * @var string
	 */
	protected $name = 'tenwebEditorSupport';

	/**
	 * The feature value. Defaults to on.
	 *
	 * @var boolean
	 */
	protected $value = true;

	/**
	 * Initialize editor support assets when the feature is enabled.
	 */
	public function initialize() {
		if ( function_exists( 'add_action' ) ) {
			add_action(
				'plugins_loaded',
				function () {
					new EditorSupport();
				}
			);
		}
	}
}
