<?php
/**
 * WP Forms.
 *
 * @package NewfoldLabs\WP\Module\Activation
 */

namespace NewfoldLabs\WP\Module\Activation\Partners;

/**
 * WP Forms class.
 */
class WpForms extends Partner {

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public function init() {
		$this->disable_redirect();

		/**
		 * Filter: 'wpforms_setup_wizard_setup_wizard_is_disabled' - Opts out of the WP Forms Setup Wizard.
		 *
		 * Returning true opts out of the first-run wizard so that new sites use our
		 * onboarding experience. The wizard remains available to the user on demand
		 * from the WP Forms Welcome page once onboarding is complete.
		 *
		 * @param bool $disabled Whether the wizard is disabled. Default false.
		 */
		add_filter( 'wpforms_setup_wizard_setup_wizard_is_disabled', '__return_true' );
	}

	/**
	 * Disable plugin activation redirect.
	 *
	 * @return void
	 */
	private function disable_redirect() {
		if ( get_option( 'wpforms_activation_redirect' ) !== true ) {
			update_option( 'wpforms_activation_redirect', true );
		}
	}
}
