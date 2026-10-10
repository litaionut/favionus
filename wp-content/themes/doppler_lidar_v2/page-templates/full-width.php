<?php
/**
 * Template Name: Full width (no sidebar)
 *
 * Used for service and legal pages (no blog sidebar / "Recent Comments" widgets).
 *
 * @package doppler_lidar
 */

get_header();
?>

<main id="primary" class="site-main wd-page-full">
	<div class="wd-container">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content', 'page' );
		endwhile;
		?>
	</div>
</main>

<?php
get_footer();
