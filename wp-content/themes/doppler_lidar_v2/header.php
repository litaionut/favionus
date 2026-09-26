<?php
/**
 * The header for our theme
 *
 * @package doppler_lidar
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'doppler_lidar' ); ?></a>

	<header id="masthead" class="site-header">
		<div class="wd-container site-header__inner">
			<?php
			if ( has_custom_logo() ) {
				the_custom_logo();
			} else {
				?>
				<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php esc_html_e( 'Favionus', 'doppler_lidar' ); ?></a>
				<?php
			}
			?>

			<nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'Primary', 'doppler_lidar' ); ?>">
				<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
					<span class="material-symbols-outlined" aria-hidden="true">menu</span>
					<span class="screen-reader-text"><?php esc_html_e( 'Primary Menu', 'doppler_lidar' ); ?></span>
				</button>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'menu-1',
						'menu_id'        => 'primary-menu',
						'menu_class'     => 'nav-menu',
						'container'      => false,
						'fallback_cb'    => 'doppler_lidar_primary_menu_fallback',
					)
				);
				?>
			</nav>

			<div class="site-header__actions">
				<a class="wd-btn wd-btn--primary" href="<?php echo esc_url( home_url( '/#contact' ) ); ?>">
					<?php esc_html_e( 'Plan a campaign', 'doppler_lidar' ); ?>
				</a>
			</div>
		</div>
	</header>
