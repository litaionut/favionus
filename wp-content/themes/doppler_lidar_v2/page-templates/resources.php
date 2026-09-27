<?php
/**
 * Research archive: every published article, with topic filters and search.
 *
 * Template Name: Resources
 *
 * @package doppler_lidar
 */

get_header();

$page        = get_queried_object();
$archive_url = doppler_lidar_resources_url();
$topic       = isset( $_GET['topic'] ) ? sanitize_title( wp_unslash( $_GET['topic'] ) ) : '';
$search      = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
$paged       = isset( $_GET['archive_page'] ) ? max( 1, absint( $_GET['archive_page'] ) ) : 1;
$dispatch    = isset( $_GET['dispatch'] ) ? sanitize_key( wp_unslash( $_GET['dispatch'] ) ) : '';

$lede = __( 'Empirical verification methodologies, sensor drift forensics, and operational campaign analyses. Calibrated for renewable lenders, met-ocean engineers, and bankable wind resource assessors.', 'doppler_lidar' );
if ( $page instanceof WP_Post && has_excerpt( $page ) ) {
	$lede = get_the_excerpt( $page );
}

$counts    = wp_count_posts( 'post' );
$published = isset( $counts->publish ) ? (int) $counts->publish : 0;
$topics    = get_categories(
	array(
		'hide_empty' => true,
	)
);

$base_args = array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'ignore_sticky_posts' => true,
	'orderby'             => 'date',
	'order'               => 'DESC',
);
if ( $topic ) {
	$base_args['category_name'] = $topic;
}
if ( $search ) {
	$base_args['s'] = $search;
}

$featured_id = 0;
$featured    = null;
if ( 1 === $paged ) {
	$featured = new WP_Query(
		array_merge(
			$base_args,
			array(
				'posts_per_page' => 1,
			)
		)
	);
	if ( $featured->have_posts() ) {
		$featured->the_post();
		$featured_id = get_the_ID();
	}
}

$grid = new WP_Query(
	array_merge(
		$base_args,
		array(
			'posts_per_page' => 9,
			'paged'          => $paged,
			'post__not_in'   => $featured_id ? array( $featured_id ) : array(),
		)
	)
);

$filters = array();
if ( $topic ) {
	$filters['topic'] = $topic;
}
if ( $search ) {
	$filters['q'] = $search;
}

$archive_link = static function ( $page_number ) use ( $archive_url, $filters ) {
	$args = $filters;
	if ( $page_number > 1 ) {
		$args['archive_page'] = $page_number;
	}
	return add_query_arg( $args, $archive_url );
};

$fallback_image = get_template_directory_uri() . '/images/archive-lidar-profile.jpg';
?>

<main id="primary" class="site-main wd-archive">
	<div class="wd-archive__strip">
		<div class="wd-container wd-archive__strip-inner">
			<div class="wd-archive__strip-id">
				<span class="wd-archive__mark" aria-hidden="true"></span>
				<span class="wd-label"><?php esc_html_e( 'Technical field notes & insights', 'doppler_lidar' ); ?></span>
			</div>
			<span class="wd-archive__badge">
				<span class="material-symbols-outlined" aria-hidden="true">verified</span>
				<?php esc_html_e( 'IEC 61400-12-1', 'doppler_lidar' ); ?>
			</span>
		</div>
	</div>

	<section class="wd-archive__hero" aria-labelledby="archive-title">
		<div class="wd-container wd-archive__hero-grid">
			<div class="wd-archive__intro">
				<div class="wd-kicker">
					<span class="wd-label wd-kicker__text wd-kicker__text--primary"><?php esc_html_e( '[Metrological archive index]', 'doppler_lidar' ); ?></span>
					<span class="wd-kicker__line" aria-hidden="true"></span>
				</div>
				<h1 id="archive-title" class="wd-headline-xl">
					<?php
					if ( $page instanceof WP_Post ) {
						echo esc_html( get_the_title( $page ) );
					} else {
						esc_html_e( 'Technical Insights & Research Archive', 'doppler_lidar' );
					}
					?>
				</h1>
				<p class="wd-body wd-lede--narrow"><?php echo esc_html( $lede ); ?></p>
			</div>

			<figure class="wd-archive__hero-media">
				<?php if ( $page instanceof WP_Post && has_post_thumbnail( $page ) ) : ?>
					<?php
					echo get_the_post_thumbnail(
						$page,
						'large',
						array(
							'class' => 'wd-archive__hero-image',
							'alt'   => esc_attr( get_the_title( $page ) ),
						)
					);
					?>
				<?php else : ?>
					<div class="wd-archive__hero-placeholder" role="img" aria-label="<?php esc_attr_e( 'Archive image', 'doppler_lidar' ); ?>"></div>
				<?php endif; ?>
			</figure>
		</div>
	</section>

	<div class="wd-archive__filters">
		<div class="wd-container wd-archive__filters-inner">
			<nav class="wd-archive__topics" aria-label="<?php esc_attr_e( 'Article topics', 'doppler_lidar' ); ?>">
				<a class="wd-archive__chip<?php echo '' === $topic ? ' is-active' : ''; ?>" href="<?php echo esc_url( $search ? add_query_arg( 'q', $search, $archive_url ) : $archive_url ); ?>">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: number of published articles. */
							__( 'All articles (%d)', 'doppler_lidar' ),
							$published
						)
					);
					?>
				</a>
				<?php foreach ( $topics as $category ) : ?>
					<a class="wd-archive__chip<?php echo $topic === $category->slug ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array_filter( array( 'topic' => $category->slug, 'q' => $search ) ), $archive_url ) ); ?>">
						<?php echo esc_html( $category->name . ' (' . sprintf( '%02d', (int) $category->count ) . ')' ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
			<form class="wd-archive__search" method="get" action="<?php echo esc_url( $archive_url ); ?>" role="search">
				<?php if ( $topic ) : ?>
					<input type="hidden" name="topic" value="<?php echo esc_attr( $topic ); ?>">
				<?php endif; ?>
				<label class="screen-reader-text" for="archive-q"><?php esc_html_e( 'Filter archive', 'doppler_lidar' ); ?></label>
				<input id="archive-q" name="q" type="search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Filter archive by keyword', 'doppler_lidar' ); ?>">
				<span class="material-symbols-outlined" aria-hidden="true">search</span>
			</form>
		</div>
	</div>

	<?php if ( $featured_id ) : ?>
		<?php
		$categories = get_the_category( $featured_id );
		$cat_name   = $categories ? $categories[0]->name : __( 'Field note', 'doppler_lidar' );
		?>
		<section class="wd-archive__featured" aria-labelledby="featured-title">
			<div class="wd-container">
				<div class="wd-archive__section-head">
					<div class="wd-archive__section-label">
						<span class="wd-archive__mark" aria-hidden="true"></span>
						<span class="wd-label"><?php esc_html_e( 'Featured paper', 'doppler_lidar' ); ?></span>
					</div>
					<span class="wd-label"><?php echo esc_html( get_the_date( 'd M Y', $featured_id ) ); ?></span>
				</div>
				<article class="wd-archive__feature">
					<span class="wd-archive__corner wd-archive__corner--tl" aria-hidden="true"></span>
					<span class="wd-archive__corner wd-archive__corner--tr" aria-hidden="true"></span>
					<span class="wd-archive__corner wd-archive__corner--bl" aria-hidden="true"></span>
					<span class="wd-archive__corner wd-archive__corner--br" aria-hidden="true"></span>
					<div class="wd-archive__feature-copy">
						<p class="wd-archive__meta">
							<span><?php echo esc_html( $cat_name ); ?></span>
							<span aria-hidden="true">•</span>
							<time datetime="<?php echo esc_attr( get_the_date( 'c', $featured_id ) ); ?>"><?php echo esc_html( get_the_date( 'd M Y', $featured_id ) ); ?></time>
							<span aria-hidden="true">•</span>
							<span><?php echo esc_html( doppler_lidar_reading_time( $featured_id ) ); ?></span>
						</p>
						<h2 id="featured-title" class="wd-headline-lg">
							<a href="<?php echo esc_url( get_permalink( $featured_id ) ); ?>"><?php echo esc_html( get_the_title( $featured_id ) ); ?></a>
						</h2>
						<p class="wd-body"><?php echo esc_html( wp_trim_words( get_the_excerpt( $featured_id ), 36 ) ); ?></p>
						<div class="wd-archive__byline">
							<span class="wd-caption wd-caption--caps"><?php esc_html_e( 'Favionus', 'doppler_lidar' ); ?></span>
							<a class="wd-btn wd-btn--primary" href="<?php echo esc_url( get_permalink( $featured_id ) ); ?>">
								<?php esc_html_e( 'Read article', 'doppler_lidar' ); ?>
								<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
							</a>
						</div>
					</div>
					<div class="wd-archive__feature-media">
						<?php if ( has_post_thumbnail( $featured_id ) ) : ?>
							<?php echo get_the_post_thumbnail( $featured_id, 'large', array( 'class' => 'wd-archive__figure', 'alt' => esc_attr( get_the_title( $featured_id ) ) ) ); ?>
						<?php else : ?>
							<img class="wd-archive__figure" src="<?php echo esc_url( $fallback_image ); ?>" alt="<?php esc_attr_e( 'Ground-based LiDAR measurement flow, from height gates to a bankable data report', 'doppler_lidar' ); ?>">
						<?php endif; ?>
					</div>
				</article>
			</div>
		</section>
		<?php
		wp_reset_postdata();
	endif;
	?>

	<?php if ( $grid->have_posts() || ! $featured_id ) : ?>
	<section class="wd-archive__list" aria-labelledby="archive-list-title">
		<div class="wd-container">
			<div class="wd-archive__section-head">
				<div class="wd-archive__section-label">
					<span class="wd-archive__mark wd-archive__mark--muted" aria-hidden="true"></span>
					<h2 id="archive-list-title" class="wd-label"><?php esc_html_e( 'All archived papers', 'doppler_lidar' ); ?></h2>
				</div>
				<?php if ( $grid->found_posts ) : ?>
					<span class="wd-label">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: articles on this page, 2: articles matching the filter. */
								__( 'Showing %1$d of %2$d', 'doppler_lidar' ),
								(int) $grid->post_count,
								(int) $grid->found_posts
							)
						);
						?>
					</span>
				<?php endif; ?>
			</div>

			<?php if ( $grid->have_posts() ) : ?>
				<div class="wd-archive__grid">
					<?php
					while ( $grid->have_posts() ) :
						$grid->the_post();
						$card_cats = get_the_category();
						$card_cat  = $card_cats ? $card_cats[0]->name : __( 'Field note', 'doppler_lidar' );
						?>
						<article <?php post_class( 'wd-archive__card' ); ?>>
							<div class="wd-archive__card-body">
								<div class="wd-archive__card-top">
									<span class="wd-archive__tag"><?php echo esc_html( $card_cat ); ?></span>
									<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'd M Y' ) ); ?></time>
								</div>
								<p class="wd-caption wd-caption--caps"><?php echo esc_html( doppler_lidar_reading_time( get_the_ID() ) ); ?></p>
								<h3 class="wd-archive__card-title">
									<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								</h3>
								<p class="wd-body"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
							</div>
							<div class="wd-archive__card-foot">
								<span class="wd-caption"><?php echo esc_html( get_the_author() ); ?></span>
								<a class="wd-text-link" href="<?php the_permalink(); ?>">
									<?php esc_html_e( 'Read article', 'doppler_lidar' ); ?>
									<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
								</a>
							</div>
						</article>
					<?php endwhile; ?>
				</div>

				<?php if ( $grid->max_num_pages > 1 ) : ?>
					<nav class="wd-archive__pager" aria-label="<?php esc_attr_e( 'Archive pages', 'doppler_lidar' ); ?>">
						<?php if ( $paged > 1 ) : ?>
							<a class="wd-archive__pager-btn" href="<?php echo esc_url( $archive_link( $paged - 1 ) ); ?>">
								<span class="material-symbols-outlined" aria-hidden="true">chevron_left</span>
								<?php esc_html_e( 'Prev', 'doppler_lidar' ); ?>
							</a>
						<?php else : ?>
							<span class="wd-archive__pager-btn is-disabled">
								<span class="material-symbols-outlined" aria-hidden="true">chevron_left</span>
								<?php esc_html_e( 'Prev', 'doppler_lidar' ); ?>
							</span>
						<?php endif; ?>
						<span class="wd-archive__pager-status">
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: current page, 2: total pages. */
									__( 'Page %1$02d / %2$02d', 'doppler_lidar' ),
									$paged,
									(int) $grid->max_num_pages
								)
							);
							?>
						</span>
						<?php if ( $paged < (int) $grid->max_num_pages ) : ?>
							<a class="wd-archive__pager-btn is-next" href="<?php echo esc_url( $archive_link( $paged + 1 ) ); ?>">
								<?php esc_html_e( 'Next', 'doppler_lidar' ); ?>
								<span class="material-symbols-outlined" aria-hidden="true">chevron_right</span>
							</a>
						<?php else : ?>
							<span class="wd-archive__pager-btn is-disabled">
								<?php esc_html_e( 'Next', 'doppler_lidar' ); ?>
								<span class="material-symbols-outlined" aria-hidden="true">chevron_right</span>
							</span>
						<?php endif; ?>
					</nav>
				<?php endif; ?>
			<?php elseif ( ! $featured_id ) : ?>
				<p class="wd-archive__empty wd-body">
					<?php
					if ( $search || $topic ) {
						esc_html_e( 'No articles match this filter.', 'doppler_lidar' );
					} else {
						esc_html_e( 'No articles have been published yet.', 'doppler_lidar' );
					}
					?>
				</p>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		</div>
	</section>
	<?php endif; ?>

	<section class="wd-archive__dispatch" aria-labelledby="dispatch-title">
		<div class="wd-container">
			<div class="wd-archive__dispatch-box">
				<div class="wd-archive__dispatch-copy">
					<p class="wd-label wd-kicker__text--primary">
						<span class="material-symbols-outlined" aria-hidden="true">mail</span>
						<?php esc_html_e( 'Get the engineering notes by email', 'doppler_lidar' ); ?>
					</p>
					<h2 id="dispatch-title" class="wd-headline-lg"><?php esc_html_e( 'Favionus monthly engineering dispatch', 'doppler_lidar' ); ?></h2>
					<p class="wd-body"><?php esc_html_e( 'Research papers, campaign notes, and data-quality standards for wind project developers and asset engineers.', 'doppler_lidar' ); ?></p>
				</div>
				<div class="wd-archive__dispatch-form">
					<?php if ( 'sent' === $dispatch ) : ?>
						<p class="wd-body" role="status"><?php esc_html_e( 'Success', 'doppler_lidar' ); ?></p>
					<?php elseif ( 'error' === $dispatch ) : ?>
						<p class="wd-body" role="alert"><?php esc_html_e( 'Enter a valid work email and try again.', 'doppler_lidar' ); ?></p>
					<?php endif; ?>
					<?php if ( 'sent' !== $dispatch ) : ?>
						<form method="post" action="<?php echo esc_url( $archive_url ); ?>">
							<?php wp_nonce_field( 'doppler_lidar_dispatch', 'doppler_lidar_dispatch_nonce' ); ?>
							<p class="wd-hp" aria-hidden="true">
								<label><?php esc_html_e( 'Fax', 'doppler_lidar' ); ?>
									<input type="text" name="wd_hp_fax" value="" tabindex="-1" autocomplete="off" inputmode="none">
								</label>
							</p>
							<label class="wd-label" for="dispatch_email"><?php esc_html_e( 'Work email', 'doppler_lidar' ); ?></label>
							<div class="wd-archive__subscribe">
								<input id="dispatch_email" name="dispatch_email" type="email" required placeholder="<?php esc_attr_e( 'eng-team@company.com', 'doppler_lidar' ); ?>">
								<button class="wd-btn wd-btn--primary" type="submit"><?php esc_html_e( 'Subscribe', 'doppler_lidar' ); ?></button>
							</div>
						</form>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
