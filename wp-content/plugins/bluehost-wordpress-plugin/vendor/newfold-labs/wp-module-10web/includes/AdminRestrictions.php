<?php
namespace NewfoldLabs\WP\Module\TenWeb;

use NewfoldLabs\WP\ModuleLoader\Container;

/**
 * Restricts theme switching and plugin access for 10Web AI editor sites.
 */
class AdminRestrictions {

	/**
	 * Approved theme slug.
	 *
	 * @var string
	 */
	const APPROVED_THEME = 'wvc-theme';

	/**
	 * Dependency injection container.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Whether to bypass the active plugins filter while the approved list is enforced.
	 *
	 * @var bool
	 */
	protected $bypass_active_plugins_filter = false;

	/**
	 * Constructor.
	 *
	 * @param Container $container The module container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;

		if ( ! $this->should_apply_restrictions() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'remove_admin_menus' ), 999 );
		add_action( 'admin_init', array( $this, 'enforce_approved_plugins' ), 5 );
		add_action( 'admin_init', array( $this, 'block_restricted_admin_pages' ) );
		add_filter( 'map_meta_cap', array( $this, 'restrict_capabilities' ), 10, 4 );
		add_filter( 'option_active_plugins', array( $this, 'filter_active_plugins' ) );
		add_filter( 'all_plugins', array( $this, 'filter_all_plugins' ) );
		add_filter( 'pre_set_theme', array( $this, 'prevent_theme_switch' ), 10, 2 );
	}

	/**
	 * Whether admin restrictions should run on this site.
	 *
	 * @return bool
	 */
	public function should_apply_restrictions() {
		if ( self::APPROVED_THEME !== get_template() ) {
			return false;
		}

		/**
		 * Filter whether TenWeb admin restrictions are enabled.
		 *
		 * @param bool $enabled Whether restrictions are enabled.
		 */
		return (bool) apply_filters( 'nfd_tenweb_admin_restrictions_enabled', true );
	}

	/**
	 * Get approved plugin basenames.
	 *
	 * @return string[]
	 */
	public function get_approved_plugin_basenames() {
		$approved = array(
			'wordpress-seo/wp-seo.php',
			'wordpress-seo-premium/wp-seo-premium.php',
			'wpseo-woocommerce/wpseo-woocommerce.php',
			'woocommerce/woocommerce.php',
			'wp-plugin-payments-shipping/wp-plugin-payments-shipping.php',
			'wp-plugin-ai-store/wp-plugin-ai-store.php',
			// 10Web AI Builder Kit. Installs the approved theme, then removes itself.
			// Approved so it is not deactivated before its self-removal can run.
			'ai-builder-kit/ai-builder-kit.php',
			'google-site-kit/google-site-kit.php',
			'akismet/akismet.php',
			'hello.php',
			$this->container->plugin()->basename, // brand plugin
		);

		/**
		 * Filter the list of approved plugin basenames.
		 *
		 * @param string[] $approved Approved plugin basenames.
		 */
		$filtered = apply_filters( 'nfd_tenweb_approved_plugins', $approved );

		// Ignore a malformed filter return rather than fataling on every request.
		// Falling back to the defaults also keeps the brand plugin approved.
		if ( is_array( $filtered ) ) {
			$approved = $filtered;
		}

		return array_values( array_unique( array_filter( $approved ) ) );
	}

	/**
	 * Deactivate active plugins that are not on the approved list.
	 *
	 * Filtering `option_active_plugins` cannot stop an unapproved plugin from
	 * loading. WordPress reads that option and includes every active plugin in
	 * wp-settings.php before `plugins_loaded` fires, and this class is only
	 * constructed on `plugins_loaded`. The filter is therefore registered after
	 * the plugins have already run, which leaves them fully loaded while
	 * `is_plugin_active()` reports them as inactive.
	 *
	 * Deactivating persists the approved set to the database so the unapproved
	 * plugins stop loading from the next request onward. The filter is kept in
	 * place as a second layer for anything reactivated outside of WP Admin.
	 *
	 * @return void
	 */
	public function enforce_approved_plugins() {
		// Network activations live in a separate site option and would be
		// deactivated across the whole network from a single site's context.
		if ( is_multisite() ) {
			return;
		}

		$approved = $this->get_approved_plugin_basenames();

		/*
		 * Never run without the brand plugin on the approved list. It carries
		 * this module, so deactivating it would remove the restrictions along
		 * with the customer's control panel.
		 */
		$brand_basename = $this->container->plugin()->basename;
		if ( empty( $brand_basename ) || ! in_array( $brand_basename, $approved, true ) ) {
			return;
		}

		/*
		 * Bypass our own filter for the duration. Both deactivate_plugins() and
		 * is_plugin_active() read active_plugins through get_option(), so while
		 * the filter is applying they would only ever see the approved subset and
		 * skip every plugin that needs deactivating.
		 *
		 * A flag is used rather than remove_filter() because has_filter() reports
		 * only the first priority a callback is attached at. If the callback were
		 * ever attached more than once, the remaining copy would keep filtering
		 * and the enforcement would silently do nothing. The flag also avoids
		 * mutating global hook state, and is restored even if a third party
		 * deactivation callback throws.
		 */
		$this->bypass_active_plugins_filter = true;

		try {
			$active     = get_option( 'active_plugins', array() );
			$unapproved = is_array( $active ) ? array_values( array_diff( $active, $approved ) ) : array();

			if ( empty( $unapproved ) ) {
				return;
			}

			if ( ! function_exists( 'deactivate_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			deactivate_plugins( $unapproved );
		} finally {
			$this->bypass_active_plugins_filter = false;
		}
	}

	/**
	 * Remove theme and plugin admin menus.
	 *
	 * @return void
	 */
	public function remove_admin_menus() {
		remove_submenu_page( 'themes.php', 'themes.php' );
		remove_submenu_page( 'themes.php', 'theme-install.php' );
		remove_submenu_page( 'themes.php', 'theme-editor.php' );
		remove_menu_page( 'plugins.php' );
	}

	/**
	 * Redirect direct requests to restricted admin pages.
	 *
	 * @return void
	 */
	public function block_restricted_admin_pages() {
		global $pagenow;

		$restricted_pages = array(
			'themes.php',
			'theme-install.php',
			'plugins.php',
			'plugin-install.php',
			'plugin-editor.php',
		);

		if ( ! in_array( $pagenow, $restricted_pages, true ) ) {
			return;
		}

		wp_safe_redirect( admin_url() );
		exit;
	}

	/**
	 * Remove capabilities required for theme and plugin management.
	 *
	 * @param string[] $caps    Required capabilities.
	 * @param string   $cap     Capability being checked.
	 * @param int      $user_id User ID.
	 * @param array    $args    Additional arguments.
	 * @return string[]
	 */
	public function restrict_capabilities( $caps, $cap, $user_id, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- map_meta_cap callback signature.
		$restricted_caps = array(
			'switch_themes',
			'edit_themes',
			'install_themes',
			'delete_themes',
			'activate_plugins',
			'install_plugins',
			'delete_plugins',
			'edit_plugins',
			'update_plugins',
		);

		if ( ! in_array( $cap, $restricted_caps, true ) ) {
			return $caps;
		}

		$caps[] = 'do_not_allow';

		return $caps;
	}

	/**
	 * Limit active plugins to the approved set.
	 *
	 * @param mixed $plugins Active plugin basenames.
	 * @return mixed
	 */
	public function filter_active_plugins( $plugins ) {
		if ( $this->bypass_active_plugins_filter || ! is_array( $plugins ) ) {
			return $plugins;
		}

		$approved = $this->get_approved_plugin_basenames();

		return array_values( array_intersect( $plugins, $approved ) );
	}

	/**
	 * Limit the plugins list shown in WP Admin.
	 *
	 * @param array $plugins All installed plugins.
	 * @return array
	 */
	public function filter_all_plugins( $plugins ) {
		if ( ! is_array( $plugins ) ) {
			return $plugins;
		}

		$approved = $this->get_approved_plugin_basenames();

		return array_intersect_key( $plugins, array_flip( $approved ) );
	}

	/**
	 * Prevent switching away from the approved theme.
	 *
	 * @param string $theme     Requested theme slug.
	 * @param string $old_theme Previous theme slug.
	 * @return string
	 */
	public function prevent_theme_switch( $theme, $old_theme ) {
		if ( self::APPROVED_THEME === $theme ) {
			return $theme;
		}

		if ( ! empty( $old_theme ) ) {
			return $old_theme;
		}

		return self::APPROVED_THEME;
	}
}
