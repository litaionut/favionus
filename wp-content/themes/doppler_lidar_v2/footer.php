<?php
/**
 * The template for displaying the footer
 *
 * @package doppler_lidar
 */

$home     = home_url( '/' );
$terms    = home_url( '/terms-of-use/' );
$privacy  = home_url( '/privacy-policy/' );
$linkedin = trim( (string) get_theme_mod( 'doppler_lidar_linkedin_url', '' ) );
$phone    = trim( (string) get_theme_mod( 'doppler_lidar_phone', '' ) );
?>

	<footer id="colophon" class="site-footer">
		<div class="wd-container">
			<div class="site-footer__grid">
				<div class="site-footer__brand">
					<a class="site-footer__brand-name" href="<?php echo esc_url( $home ); ?>"><?php esc_html_e( 'Favionus', 'doppler_lidar' ); ?></a>
					<p class="wd-body"><?php esc_html_e( 'Wind measurement campaigns for onshore wind projects. Delivering bankable data through technical excellence.', 'doppler_lidar' ); ?></p>
					<div class="site-footer__contact">
						<p class="wd-caption">
							<span class="material-symbols-outlined" aria-hidden="true">location_on</span>
							<?php esc_html_e( 'Bucharest, Romania', 'doppler_lidar' ); ?>
						</p>
						<p class="wd-caption">
							<span class="material-symbols-outlined" aria-hidden="true">mail</span>
							<a href="mailto:contact@favionus.com">contact@favionus.com</a>
						</p>
						<?php if ( $phone !== '' ) : ?>
						<p class="wd-caption">
							<span class="material-symbols-outlined" aria-hidden="true">call</span>
							<a href="<?php echo esc_url( 'tel:' . preg_replace( '/\s+/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
						</p>
						<?php endif; ?>
					</div>
					<?php if ( $linkedin !== '' ) : ?>
					<a class="site-footer__social" href="<?php echo esc_url( $linkedin ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'LinkedIn', 'doppler_lidar' ); ?>">
						<svg class="site-footer__linkedin" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"></path></svg>
					</a>
					<?php endif; ?>
				</div>

				<div>
					<p class="wd-label"><?php esc_html_e( 'Company', 'doppler_lidar' ); ?></p>
					<nav class="site-footer__links" aria-label="<?php esc_attr_e( 'Footer', 'doppler_lidar' ); ?>">
						<a href="<?php echo esc_url( $home . '#how-it-works' ); ?>"><?php esc_html_e( 'LiDAR Campaigns', 'doppler_lidar' ); ?></a>
						<a href="<?php echo esc_url( $home . '#data-quality' ); ?>"><?php esc_html_e( 'Data Quality', 'doppler_lidar' ); ?></a>
						<a href="<?php echo esc_url( $home . '#technology' ); ?>"><?php esc_html_e( 'Technology', 'doppler_lidar' ); ?></a>
						<a href="<?php echo esc_url( home_url( '/wind-lidar-measurement-campaigns/' ) ); ?>"><?php esc_html_e( 'Services', 'doppler_lidar' ); ?></a>
						<a href="<?php echo esc_url( $home . '#contact' ); ?>"><?php esc_html_e( 'Contact', 'doppler_lidar' ); ?></a>
					</nav>
				</div>

				<div class="site-footer__legal-col">
					<p class="wd-label"><?php esc_html_e( 'Legal Information', 'doppler_lidar' ); ?></p>
					<div class="site-footer__legal">
						<p><?php esc_html_e( 'Favionus S.R.L.', 'doppler_lidar' ); ?></p>
						<p><?php esc_html_e( 'Reg. No: J40/12345/2024', 'doppler_lidar' ); ?></p>
						<p><?php esc_html_e( 'VAT: RO123456789', 'doppler_lidar' ); ?></p>
					</div>
					<div class="site-footer__legal-nav">
						<a href="<?php echo esc_url( $terms ); ?>"><?php esc_html_e( 'Terms', 'doppler_lidar' ); ?></a>
						<a href="<?php echo esc_url( $privacy ); ?>"><?php esc_html_e( 'Privacy', 'doppler_lidar' ); ?></a>
					</div>
				</div>
			</div>

			<div class="site-footer__bottom">
				<p class="site-footer__copy">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php esc_html_e( 'Favionus. All rights reserved.', 'doppler_lidar' ); ?></p>
				<p class="wd-caption"><?php esc_html_e( 'Aero-Technical Precision Design System', 'doppler_lidar' ); ?></p>
			</div>
		</div>
	</footer>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
