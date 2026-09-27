<?php
/**
 * The front page template
 *
 * @package doppler_lidar
 */

get_header();

$status = isset( $_GET['campaign'] ) ? sanitize_key( wp_unslash( $_GET['campaign'] ) ) : '';
$lidar  = doppler_lidar_skin_image( 'lidar' );
?>

<main id="primary" class="site-main">

	<section class="wd-hero wd-container" aria-labelledby="hero-title">
		<div class="wd-hero__copy">
			<div class="wd-kicker">
				<span class="wd-label wd-kicker__text"><?php esc_html_e( 'Turnkey Solution', 'doppler_lidar' ); ?></span>
				<span class="wd-kicker__line" aria-hidden="true"></span>
			</div>
			<h1 id="hero-title" class="wd-headline-xl">
				<?php esc_html_e( 'Bankable wind data.', 'doppler_lidar' ); ?><br>
				<?php esc_html_e( 'Delivered turnkey.', 'doppler_lidar' ); ?>
			</h1>
			<p class="wd-body wd-lede--narrow">
				<?php esc_html_e( 'Complete LiDAR measurement campaigns for onshore wind projects — from deployment and monitoring to quality-controlled data delivery.', 'doppler_lidar' ); ?>
			</p>
			<div class="wd-hero__actions">
				<a class="wd-btn wd-btn--primary wd-btn--lg" href="#contact"><?php esc_html_e( 'Plan a campaign', 'doppler_lidar' ); ?></a>
				<button type="button" class="wd-btn wd-btn--ghost wd-btn--lg" data-wd-modal="fleet-modal">
					<?php esc_html_e( 'Check fleet availability', 'doppler_lidar' ); ?>
					<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
				</button>
			</div>
		</div>
		<div class="wd-hero__media">
			<div class="wd-schematic">
				<span class="wd-schematic__corner wd-schematic__corner--tl" aria-hidden="true"></span>
				<span class="wd-schematic__corner wd-schematic__corner--tr" aria-hidden="true"></span>
				<span class="wd-schematic__corner wd-schematic__corner--bl" aria-hidden="true"></span>
				<span class="wd-schematic__corner wd-schematic__corner--br" aria-hidden="true"></span>
				<?php
				if ( 'vane' === doppler_lidar_get_skin() ) {
					$lidar_svg = get_template_directory() . '/images/lidar-vane.svg';
					if ( file_exists( $lidar_svg ) ) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme SVG.
						echo file_get_contents( $lidar_svg );
					}
				} else {
					?>
					<img src="<?php echo esc_url( $lidar ); ?>" alt="<?php esc_attr_e( 'ZX-300e LiDAR Unit', 'doppler_lidar' ); ?>">
					<?php
				}
				?>
			</div>
		</div>
	</section>

	<section class="wd-strip" aria-label="<?php esc_attr_e( 'Capabilities', 'doppler_lidar' ); ?>">
		<div class="wd-container">
			<div class="wd-strip__grid">
				<div class="wd-strip__item">
					<span class="material-symbols-outlined" aria-hidden="true">verified</span>
					<div>
						<span class="wd-label"><?php esc_html_e( 'Standard', 'doppler_lidar' ); ?></span>
						<span class="wd-strip__title"><?php esc_html_e( 'IEC-Classified LiDAR Technology', 'doppler_lidar' ); ?></span>
					</div>
				</div>
				<div class="wd-strip__item">
					<span class="material-symbols-outlined" aria-hidden="true">monitoring</span>
					<div>
						<span class="wd-label"><?php esc_html_e( 'Uptime', 'doppler_lidar' ); ?></span>
						<span class="wd-strip__title"><?php esc_html_e( '24/7 Remote Monitoring', 'doppler_lidar' ); ?></span>
					</div>
				</div>
				<div class="wd-strip__item">
					<span class="material-symbols-outlined" aria-hidden="true">precision_manufacturing</span>
					<div>
						<span class="wd-label"><?php esc_html_e( 'Reliability', 'doppler_lidar' ); ?></span>
						<span class="wd-strip__title"><?php esc_html_e( 'Autonomous Operation', 'doppler_lidar' ); ?></span>
					</div>
				</div>
				<div class="wd-strip__item">
					<span class="material-symbols-outlined" aria-hidden="true">analytics</span>
					<div>
						<span class="wd-label"><?php esc_html_e( 'Integrity', 'doppler_lidar' ); ?></span>
						<span class="wd-strip__title"><?php esc_html_e( 'Quality-Controlled Data', 'doppler_lidar' ); ?></span>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="wd-section wd-section--auto" aria-labelledby="problem-title">
		<div class="wd-container">
			<div class="wd-center">
				<h2 id="problem-title" class="wd-headline-lg"><?php esc_html_e( 'Reliable data. Less complexity.', 'doppler_lidar' ); ?></h2>
				<p class="wd-body">
					<?php esc_html_e( 'A successful wind measurement campaign requires more than a LiDAR. We combine measurement technology, autonomous power, remote monitoring and wind engineering expertise into one managed service.', 'doppler_lidar' ); ?>
				</p>
			</div>
			<div class="wd-feature-row">
				<div>
					<h3 class="wd-label"><?php esc_html_e( 'Complete Campaign', 'doppler_lidar' ); ?></h3>
					<p class="wd-body"><?php esc_html_e( 'Planning, deployment, commissioning and decommissioning.', 'doppler_lidar' ); ?></p>
				</div>
				<div class="wd-feature-row__mid">
					<h3 class="wd-label"><?php esc_html_e( 'Continuous Monitoring', 'doppler_lidar' ); ?></h3>
					<p class="wd-body"><?php esc_html_e( 'Remote monitoring of equipment performance and incoming data.', 'doppler_lidar' ); ?></p>
				</div>
				<div>
					<h3 class="wd-label"><?php esc_html_e( 'Quality-Controlled Data', 'doppler_lidar' ); ?></h3>
					<p class="wd-body"><?php esc_html_e( 'Structured datasets and reporting ready for your engineering workflow.', 'doppler_lidar' ); ?></p>
				</div>
			</div>
		</div>
	</section>

	<section id="how-it-works" class="wd-section wd-section--auto wd-section--border" aria-labelledby="how-title">
		<div class="wd-container">
			<h2 id="how-title" class="wd-headline-lg wd-section-title"><?php esc_html_e( 'From site to dataset.', 'doppler_lidar' ); ?></h2>
			<div class="wd-timeline">
				<div class="wd-timeline__line" aria-hidden="true"></div>
				<div class="wd-step">
					<span class="wd-step__dot" aria-hidden="true"><span></span></span>
					<h3 class="wd-label wd-step__label"><?php esc_html_e( '01 — Plan', 'doppler_lidar' ); ?></h3>
					<p class="wd-body"><?php esc_html_e( 'We define measurement objectives, heights, campaign duration and site requirements.', 'doppler_lidar' ); ?></p>
				</div>
				<div class="wd-step">
					<span class="wd-step__dot" aria-hidden="true"><span></span></span>
					<h3 class="wd-label wd-step__label"><?php esc_html_e( '02 — Deploy', 'doppler_lidar' ); ?></h3>
					<p class="wd-body"><?php esc_html_e( 'We transport, install and commission the LiDAR and autonomous power system.', 'doppler_lidar' ); ?></p>
				</div>
				<div class="wd-step">
					<span class="wd-step__dot" aria-hidden="true"><span></span></span>
					<h3 class="wd-label wd-step__label"><?php esc_html_e( '03 — Monitor', 'doppler_lidar' ); ?></h3>
					<p class="wd-body"><?php esc_html_e( 'System performance and incoming measurements are continuously monitored.', 'doppler_lidar' ); ?></p>
				</div>
				<div class="wd-step">
					<span class="wd-step__dot" aria-hidden="true"><span></span></span>
					<h3 class="wd-label wd-step__label"><?php esc_html_e( '04 — Deliver', 'doppler_lidar' ); ?></h3>
					<p class="wd-body"><?php esc_html_e( 'Quality-controlled wind data and campaign reporting are delivered throughout the project.', 'doppler_lidar' ); ?></p>
				</div>
			</div>
		</div>
	</section>

	<section id="data-quality" class="wd-section wd-section--auto wd-section--border" aria-labelledby="quality-title">
		<div class="wd-container wd-two">
			<div class="wd-two__copy">
				<h2 id="quality-title" class="wd-headline-lg"><?php esc_html_e( 'Data you can build decisions on.', 'doppler_lidar' ); ?></h2>
				<p class="wd-body wd-lede--narrow"><?php esc_html_e( 'Wind measurements influence energy yield assessments, turbine selection and investment decisions. Campaign quality cannot be an afterthought.', 'doppler_lidar' ); ?></p>
				<ul class="wd-checklist">
					<li><span class="material-symbols-outlined" aria-hidden="true">check_circle</span><span><?php esc_html_e( 'IEC-classified LiDAR technology', 'doppler_lidar' ); ?></span></li>
					<li><span class="material-symbols-outlined" aria-hidden="true">description</span><span><?php esc_html_e( 'Documented commissioning', 'doppler_lidar' ); ?></span></li>
					<li><span class="material-symbols-outlined" aria-hidden="true">sensors</span><span><?php esc_html_e( 'Remote monitoring', 'doppler_lidar' ); ?></span></li>
					<li><span class="material-symbols-outlined" aria-hidden="true">rule</span><span><?php esc_html_e( 'Systematic quality control', 'doppler_lidar' ); ?></span></li>
					<li><span class="material-symbols-outlined" aria-hidden="true">history_edu</span><span><?php esc_html_e( 'Traceable campaign reporting', 'doppler_lidar' ); ?></span></li>
				</ul>
				<a class="wd-text-link" href="#data-quality">
					<?php esc_html_e( 'Explore our data quality process', 'doppler_lidar' ); ?>
					<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
				</a>
			</div>
			<div class="wd-window">
				<div class="wd-window__bar" aria-hidden="true">
					<span></span><span></span><span></span>
				</div>
				<div class="wd-dash" aria-label="<?php esc_attr_e( 'Wind Data Analytics Dashboard', 'doppler_lidar' ); ?>">
					<div class="wd-dash__head">
						<div>
							<p class="wd-dash__title"><?php esc_html_e( 'Executive Summary', 'doppler_lidar' ); ?></p>
							<p class="wd-caption wd-dash__meta">
								<span class="material-symbols-outlined" aria-hidden="true">location_on</span>
								<?php esc_html_e( 'Site Alpha-7 | 45.4215° N, 75.6972° W', 'doppler_lidar' ); ?>
							</p>
						</div>
						<div class="wd-dash__status">
							<span class="wd-label"><?php esc_html_e( 'Status', 'doppler_lidar' ); ?></span>
							<span><?php esc_html_e( 'Bankable data: verified', 'doppler_lidar' ); ?></span>
						</div>
					</div>

					<div class="wd-dash__row">
						<div class="wd-dash__panel wd-dash__panel--gauge">
							<span class="wd-schematic__corner wd-schematic__corner--tl" aria-hidden="true"></span>
							<span class="wd-schematic__corner wd-schematic__corner--tr" aria-hidden="true"></span>
							<span class="wd-schematic__corner wd-schematic__corner--bl" aria-hidden="true"></span>
							<span class="wd-schematic__corner wd-schematic__corner--br" aria-hidden="true"></span>
							<h3 class="wd-label"><?php esc_html_e( 'Data Availability', 'doppler_lidar' ); ?></h3>
							<div class="wd-gauge">
								<svg viewBox="0 0 100 100" aria-hidden="true">
									<circle class="wd-gauge__track" cx="50" cy="50" r="42"></circle>
									<circle class="wd-gauge__value" cx="50" cy="50" r="42"></circle>
								</svg>
								<div class="wd-gauge__readout">
									<span>99.9%</span>
									<small><?php esc_html_e( 'Uptime', 'doppler_lidar' ); ?></small>
								</div>
							</div>
						</div>

						<div class="wd-dash__panel wd-dash__panel--chart">
							<span class="wd-schematic__corner wd-schematic__corner--tl" aria-hidden="true"></span>
							<span class="wd-schematic__corner wd-schematic__corner--tr" aria-hidden="true"></span>
							<span class="wd-schematic__corner wd-schematic__corner--bl" aria-hidden="true"></span>
							<span class="wd-schematic__corner wd-schematic__corner--br" aria-hidden="true"></span>
							<div class="wd-dash__panel-head">
								<h3 class="wd-label"><?php esc_html_e( '7-Day Wind Speed Trend', 'doppler_lidar' ); ?></h3>
								<div class="wd-dash__legend">
									<span><i></i><?php esc_html_e( 'Avg', 'doppler_lidar' ); ?></span>
									<span class="wd-dash__legend--muted"><i></i><?php esc_html_e( 'Max Gust', 'doppler_lidar' ); ?></span>
								</div>
							</div>
							<div class="wd-chart">
								<div class="wd-chart__y" aria-hidden="true"><span>25</span><span>15</span><span>5</span></div>
								<svg class="wd-chart__svg" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
									<polyline class="wd-chart__avg" points="0,70 15,65 30,80 45,60 60,75 75,50 90,65 100,55"></polyline>
									<polyline class="wd-chart__gust" points="0,90 15,85 30,95 45,80 60,90 75,70 90,85 100,75"></polyline>
								</svg>
								<div class="wd-chart__x" aria-hidden="true"><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span></div>
							</div>
						</div>
					</div>

					<div class="wd-dash__row wd-dash__row--bottom">
						<div class="wd-dash__panel">
							<span class="wd-schematic__corner wd-schematic__corner--tl" aria-hidden="true"></span>
							<span class="wd-schematic__corner wd-schematic__corner--tr" aria-hidden="true"></span>
							<span class="wd-schematic__corner wd-schematic__corner--bl" aria-hidden="true"></span>
							<span class="wd-schematic__corner wd-schematic__corner--br" aria-hidden="true"></span>
							<h3 class="wd-label"><?php esc_html_e( 'Key Campaign Averages', 'doppler_lidar' ); ?></h3>
							<table class="wd-dash__table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Parameter', 'doppler_lidar' ); ?></th>
										<th><?php esc_html_e( 'Height', 'doppler_lidar' ); ?></th>
										<th><?php esc_html_e( 'Mean', 'doppler_lidar' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td><?php esc_html_e( 'Wind Speed', 'doppler_lidar' ); ?></td>
										<td>120 m</td>
										<td>8.4 m/s</td>
									</tr>
									<tr>
										<td><?php esc_html_e( 'Wind Direction', 'doppler_lidar' ); ?></td>
										<td>118 m</td>
										<td>215° SW</td>
									</tr>
									<tr>
										<td><?php esc_html_e( 'Turbulence Intensity', 'doppler_lidar' ); ?></td>
										<td>120 m</td>
										<td>0.11</td>
									</tr>
								</tbody>
							</table>
						</div>

						<div class="wd-dash__panel wd-dash__panel--compass">
							<span class="wd-schematic__corner wd-schematic__corner--tl" aria-hidden="true"></span>
							<span class="wd-schematic__corner wd-schematic__corner--tr" aria-hidden="true"></span>
							<span class="wd-schematic__corner wd-schematic__corner--bl" aria-hidden="true"></span>
							<span class="wd-schematic__corner wd-schematic__corner--br" aria-hidden="true"></span>
							<h3 class="wd-label"><?php esc_html_e( 'Prevailing Direction', 'doppler_lidar' ); ?></h3>
							<div class="wd-compass">
								<span>N</span><span>E</span><span>S</span><span>W</span>
								<div class="wd-compass__needle" aria-hidden="true"></div>
							</div>
							<p class="wd-data">215°</p>
							<p class="wd-caption wd-caption--caps"><?php esc_html_e( 'South-West', 'doppler_lidar' ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section id="use-cases" class="wd-section wd-section--auto wd-section--border" aria-labelledby="usecases-title">
		<div class="wd-container">
			<h2 id="usecases-title" class="wd-headline-lg wd-section-title"><?php esc_html_e( 'Built for real wind projects.', 'doppler_lidar' ); ?></h2>
			<div class="wd-cases">
				<article class="wd-case">
					<span class="wd-case__num" aria-hidden="true">01</span>
					<span class="material-symbols-outlined" aria-hidden="true">assessment</span>
					<h3 class="wd-label"><?php esc_html_e( 'Wind Resource Assessment', 'doppler_lidar' ); ?></h3>
					<p class="wd-body"><?php esc_html_e( 'Collect wind measurements for greenfield project development.', 'doppler_lidar' ); ?></p>
				</article>
				<article class="wd-case">
					<span class="wd-case__num" aria-hidden="true">02</span>
					<span class="material-symbols-outlined" aria-hidden="true">height</span>
					<h3 class="wd-label"><?php esc_html_e( 'Met Mast Complement', 'doppler_lidar' ); ?></h3>
					<p class="wd-body"><?php esc_html_e( 'Extend measurement heights and improve understanding of the rotor-swept wind profile.', 'doppler_lidar' ); ?></p>
				</article>
				<article class="wd-case">
					<span class="wd-case__num" aria-hidden="true">03</span>
					<span class="material-symbols-outlined" aria-hidden="true">autorenew</span>
					<h3 class="wd-label"><?php esc_html_e( 'Repowering', 'doppler_lidar' ); ?></h3>
					<p class="wd-body"><?php esc_html_e( 'Measure wind conditions at modern turbine hub and rotor heights.', 'doppler_lidar' ); ?></p>
				</article>
				<article class="wd-case">
					<span class="wd-case__num" aria-hidden="true">04</span>
					<span class="material-symbols-outlined" aria-hidden="true">explore</span>
					<h3 class="wd-label"><?php esc_html_e( 'Site Investigation', 'doppler_lidar' ); ?></h3>
					<p class="wd-body"><?php esc_html_e( 'Quickly characterise prospective wind farm locations.', 'doppler_lidar' ); ?></p>
				</article>
			</div>
		</div>
	</section>

	<section id="technology" class="wd-section wd-section--auto wd-section--muted wd-section--border" aria-labelledby="tech-title">
		<div class="wd-container wd-two">
			<div class="wd-two__copy">
				<div class="wd-kicker wd-kicker--short">
					<span class="wd-kicker__rule" aria-hidden="true"></span>
					<span class="wd-label wd-kicker__text"><?php esc_html_e( 'Technology', 'doppler_lidar' ); ?></span>
				</div>
				<h2 id="tech-title" class="wd-headline-lg"><?php esc_html_e( 'Professional vertical profiling LiDAR', 'doppler_lidar' ); ?></h2>
				<p class="wd-body"><?php esc_html_e( 'Our campaigns use industry-proven Doppler LiDAR technology capable of measuring the wind profile across modern turbine rotor heights.', 'doppler_lidar' ); ?></p>
				<a class="wd-text-link" href="#technology">
					<?php esc_html_e( 'Explore the technology', 'doppler_lidar' ); ?>
					<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
				</a>
			</div>
			<figure class="wd-field-shot">
				<img
					src="<?php echo esc_url( get_template_directory_uri() . '/images/profiling-field.png' ); ?>"
					alt="<?php esc_attr_e( 'Vertical wind profiling LiDAR installed beside a wind farm', 'doppler_lidar' ); ?>"
					width="1200"
					height="675"
					loading="lazy"
					decoding="async"
				>
				<span class="wd-field-shot__layer" aria-hidden="true">
					<span class="wd-field-shot__grade"></span>
					<span class="wd-field-shot__felt"></span>
					<span class="wd-field-shot__grid"></span>
				</span>
			</figure>
		</div>
	</section>

	<section id="sample-report" class="wd-section wd-section--auto wd-section--border" aria-labelledby="sample-title">
		<div class="wd-container wd-two wd-two--7-5">
			<div class="wd-two__copy">
				<div class="wd-kicker wd-kicker--short">
					<span class="wd-kicker__rule wd-kicker__rule--primary" aria-hidden="true"></span>
					<span class="wd-label wd-kicker__text wd-kicker__text--primary"><?php esc_html_e( 'Sample Campaign Report', 'doppler_lidar' ); ?></span>
				</div>
				<h2 id="sample-title" class="wd-headline-lg"><?php esc_html_e( 'And we show a real excerpt on the homepage.', 'doppler_lidar' ); ?></h2>
				<p class="wd-body"><?php esc_html_e( 'Our reporting provides full transparency into campaign performance, ensuring every data point is verified and traceable to international standards.', 'doppler_lidar' ); ?></p>
				<ul class="wd-checklist wd-checklist--compact">
					<li><span class="material-symbols-outlined" aria-hidden="true">analytics</span><span><?php esc_html_e( 'Monthly statistics', 'doppler_lidar' ); ?></span></li>
					<li><span class="material-symbols-outlined" aria-hidden="true">settings_input_component</span><span><?php esc_html_e( 'Equipment status', 'doppler_lidar' ); ?></span></li>
					<li><span class="material-symbols-outlined" aria-hidden="true">fact_check</span><span><?php esc_html_e( 'QC observations', 'doppler_lidar' ); ?></span></li>
				</ul>
				<a class="wd-btn wd-btn--primary wd-btn--lg" href="#sample-report"><?php esc_html_e( 'View Sample Campaign Report', 'doppler_lidar' ); ?></a>
			</div>
			<div class="wd-report">
				<div class="wd-report__top">
					<div>
						<p class="wd-report__brand"><?php esc_html_e( 'Favionus', 'doppler_lidar' ); ?></p>
						<p class="wd-caption wd-caption--caps"><?php esc_html_e( 'Campaign Status Report', 'doppler_lidar' ); ?></p>
					</div>
					<div class="wd-report__metric">
						<p class="wd-caption wd-caption--caps"><?php esc_html_e( 'Availability', 'doppler_lidar' ); ?></p>
						<p class="wd-report__value">98.7%</p>
					</div>
				</div>
				<div class="wd-report__grid">
					<div class="wd-rose" aria-hidden="true">
						<div class="wd-rose__rings">
							<span class="wd-rose__ring wd-rose__ring--outer"></span>
							<span class="wd-rose__ring wd-rose__ring--mid"></span>
							<span class="wd-rose__ring wd-rose__ring--inner"></span>
							<svg viewBox="0 0 100 100">
								<g class="wd-rose__grid" fill="none" stroke="currentColor" stroke-width="0.35">
									<line x1="50" y1="50" x2="50" y2="2"/>
									<line x1="50" y1="50" x2="74" y2="8.4"/>
									<line x1="50" y1="50" x2="91.6" y2="26"/>
									<line x1="50" y1="50" x2="98" y2="50"/>
									<line x1="50" y1="50" x2="91.6" y2="74"/>
									<line x1="50" y1="50" x2="74" y2="91.6"/>
									<line x1="50" y1="50" x2="50" y2="98"/>
									<line x1="50" y1="50" x2="26" y2="91.6"/>
									<line x1="50" y1="50" x2="8.4" y2="74"/>
									<line x1="50" y1="50" x2="2" y2="50"/>
									<line x1="50" y1="50" x2="8.4" y2="26"/>
									<line x1="50" y1="50" x2="26" y2="8.4"/>
								</g>
								<g class="wd-rose__petals" fill="currentColor">
									<path fill-opacity="0.22" d="M50,50 L46.37,36.48 L53.63,36.48 Z"/>
									<path fill-opacity="0.18" d="M50,50 L52.59,40.34 L57.07,42.93 Z"/>
									<path fill-opacity="0.2" d="M50,50 L58.48,41.52 L61.59,46.89 Z"/>
									<path fill-opacity="0.24" d="M50,50 L65.46,45.86 L65.46,54.14 Z"/>
									<path fill-opacity="0.18" d="M50,50 L60.63,52.85 L57.78,57.78 Z"/>
									<path fill-opacity="0.22" d="M50,50 L60.61,60.61 L53.89,64.49 Z"/>
									<path fill-opacity="0.28" d="M50,50 L55.18,69.32 L44.82,69.32 Z"/>
									<path fill-opacity="0.38" d="M50,50 L42.75,77.05 L30.20,69.80 Z"/>
									<path fill-opacity="0.55" d="M50,50 L20.31,79.69 L9.43,60.88 Z"/>
									<path fill-opacity="0.6" d="M50,50 L6.53,61.66 L6.53,38.34 Z"/>
									<path fill-opacity="0.34" d="M50,50 L24.88,43.27 L31.62,31.62 Z"/>
									<path fill-opacity="0.26" d="M50,50 L37.27,37.27 L45.34,32.61 Z"/>
								</g>
							</svg>
							<span class="wd-rose__dir wd-rose__dir--n">N</span>
							<span class="wd-rose__dir wd-rose__dir--e">E</span>
							<span class="wd-rose__dir wd-rose__dir--s">S</span>
							<span class="wd-rose__dir wd-rose__dir--w">W</span>
						</div>
						<p class="wd-caption wd-caption--caps"><?php esc_html_e( 'Wind Rose Analysis', 'doppler_lidar' ); ?></p>
					</div>
					<div class="wd-report__stats">
						<div>
							<p class="wd-caption wd-caption--caps"><?php esc_html_e( 'Mean Wind Speed', 'doppler_lidar' ); ?></p>
							<p class="wd-data">8.42 m/s</p>
						</div>
						<div>
							<p class="wd-caption wd-caption--caps"><?php esc_html_e( 'Turbulence Intensity', 'doppler_lidar' ); ?></p>
							<p class="wd-data">0.124</p>
						</div>
						<div>
							<p class="wd-caption wd-caption--caps"><?php esc_html_e( 'TI Class', 'doppler_lidar' ); ?></p>
							<p class="wd-data"><?php esc_html_e( 'IEC Class A', 'doppler_lidar' ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section id="contact" class="wd-section wd-section--auto wd-section--border wd-contact" aria-labelledby="contact-title">
		<div class="wd-container wd-contact__inner">
			<h2 id="contact-title" class="wd-headline-lg"><?php esc_html_e( 'Plan a campaign or have questions?', 'doppler_lidar' ); ?></h2>
			<p class="wd-body">
				<?php esc_html_e( 'Let\'s plan the most suited campaign for your project.', 'doppler_lidar' ); ?>
			</p>

			<?php if ( 'sent' === $status ) : ?>
				<p class="wd-notice wd-notice--success" id="campaign-success" role="status" tabindex="-1"><?php esc_html_e( 'Message received. We will come back to discuss the most suited campaign for your project.', 'doppler_lidar' ); ?></p>
			<?php else : ?>
				<p class="wd-notice wd-notice--success" id="campaign-success" role="status" tabindex="-1" hidden><?php esc_html_e( 'Message received. We will come back to discuss the most suited campaign for your project.', 'doppler_lidar' ); ?></p>
			<?php endif; ?>
			<?php if ( 'error' === $status ) : ?>
				<p class="wd-notice wd-notice--error" id="campaign-error" role="alert"><?php esc_html_e( 'Please fill in your name and a valid work email, then try again.', 'doppler_lidar' ); ?></p>
			<?php else : ?>
				<p class="wd-notice wd-notice--error" id="campaign-error" role="alert" hidden><?php esc_html_e( 'Please fill in your name and a valid work email, then try again.', 'doppler_lidar' ); ?></p>
			<?php endif; ?>

			<?php if ( 'sent' !== $status ) : ?>
			<form id="campaign-form" class="wd-form" method="post" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php wp_nonce_field( 'doppler_lidar_campaign', 'doppler_lidar_campaign_nonce' ); ?>
				<p class="wd-hp" aria-hidden="true">
					<label><?php esc_html_e( 'Fax', 'doppler_lidar' ); ?>
						<input type="text" name="wd_hp_fax" value="" tabindex="-1" autocomplete="off" inputmode="none">
					</label>
				</p>
				<div class="wd-form__row">
					<div class="wd-field">
						<label class="wd-label" for="campaign_name"><?php esc_html_e( 'Name', 'doppler_lidar' ); ?></label>
						<input id="campaign_name" name="campaign_name" type="text" required>
					</div>
					<div class="wd-field">
						<label class="wd-label" for="campaign_email"><?php esc_html_e( 'Work Email', 'doppler_lidar' ); ?></label>
						<input id="campaign_email" name="campaign_email" type="email" required>
					</div>
					<div class="wd-field">
						<label class="wd-label" for="campaign_location"><?php esc_html_e( 'Country', 'doppler_lidar' ); ?></label>
						<input id="campaign_location" name="campaign_location" type="text" required>
					</div>
					<div class="wd-field">
						<label class="wd-label" for="campaign_start"><?php esc_html_e( 'Expected Start', 'doppler_lidar' ); ?></label>
						<input id="campaign_start" name="campaign_start" type="text" required>
					</div>
				</div>
				<div class="wd-field">
					<label class="wd-label" for="campaign_notes"><?php esc_html_e( 'Anything Else? (Optional)', 'doppler_lidar' ); ?></label>
					<textarea id="campaign_notes" name="campaign_notes" rows="3"></textarea>
				</div>
				<div class="wd-form__submit">
					<button class="wd-btn wd-btn--primary wd-btn--lg" type="submit"><?php esc_html_e( 'Request a Campaign', 'doppler_lidar' ); ?></button>
				</div>
			</form>
			<?php endif; ?>
		</div>
	</section>

	<dialog id="fleet-modal" class="wd-modal" aria-labelledby="fleet-modal-title">
		<div class="wd-modal__head">
			<div>
				<p class="wd-label" id="fleet-modal-kicker"><?php esc_html_e( 'Fleet', 'doppler_lidar' ); ?></p>
				<h2 class="wd-headline-lg" id="fleet-modal-title"><?php esc_html_e( 'Check availability', 'doppler_lidar' ); ?></h2>
			</div>
			<button type="button" class="wd-modal__close" data-wd-modal-close aria-label="<?php esc_attr_e( 'Close', 'doppler_lidar' ); ?>">
				<span class="material-symbols-outlined" aria-hidden="true">close</span>
			</button>
		</div>

		<form class="wd-form wd-modal__form" id="fleet-form" method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
			<?php wp_nonce_field( 'doppler_lidar_fleet', 'doppler_lidar_fleet_nonce' ); ?>
			<input type="hidden" name="action" value="doppler_lidar_fleet">
			<p class="wd-hp" aria-hidden="true">
				<label><?php esc_html_e( 'Fax', 'doppler_lidar' ); ?>
					<input type="text" name="wd_hp_fax" value="" tabindex="-1" autocomplete="off" inputmode="none">
				</label>
			</p>

			<div class="wd-modal__step is-active" data-fleet-step="ask">
				<p class="wd-body"><?php esc_html_e( 'Tell us where and how many units you need. We will show current fleet availability.', 'doppler_lidar' ); ?></p>

				<div class="wd-field">
					<label class="wd-label" for="fleet_country-toggle"><?php esc_html_e( 'Country', 'doppler_lidar' ); ?></label>
					<div class="wd-select" data-wd-select>
						<select id="fleet_country" class="wd-select__native" name="fleet_country" required>
							<option value=""><?php esc_html_e( 'Select country', 'doppler_lidar' ); ?></option>
							<?php foreach ( doppler_lidar_fleet_countries() as $code => $label ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<fieldset class="wd-field">
					<legend class="wd-label"><?php esc_html_e( 'Estimated data need', 'doppler_lidar' ); ?></legend>
					<div class="wd-modal__choices">
						<label class="wd-choice">
							<input type="radio" name="fleet_duration" value="lt1" required>
							<span><?php esc_html_e( '< 1 month', 'doppler_lidar' ); ?></span>
						</label>
						<label class="wd-choice">
							<input type="radio" name="fleet_duration" value="2-3">
							<span><?php esc_html_e( '2–3 months', 'doppler_lidar' ); ?></span>
						</label>
						<label class="wd-choice">
							<input type="radio" name="fleet_duration" value="gt3">
							<span><?php esc_html_e( '> 3 months', 'doppler_lidar' ); ?></span>
						</label>
					</div>
				</fieldset>

				<div class="wd-field">
					<label class="wd-label" for="fleet_units"><?php esc_html_e( 'Number of units', 'doppler_lidar' ); ?></label>
					<input id="fleet_units" name="fleet_units" type="number" min="1" max="99" step="1" inputmode="numeric" required>
				</div>

				<div class="wd-form__submit">
					<button class="wd-btn wd-btn--primary wd-btn--lg" type="submit" data-fleet-check>
						<?php esc_html_e( 'Check availability', 'doppler_lidar' ); ?>
					</button>
				</div>
			</div>

			<div class="wd-modal__step" data-fleet-step="loading" hidden>
				<div class="wd-fleet-loading" role="status" aria-live="polite">
					<span class="wd-fleet-loading__icon" aria-hidden="true"></span>
					<p class="wd-label"><?php esc_html_e( 'Retrieving info from database', 'doppler_lidar' ); ?></p>
				</div>
			</div>

			<div class="wd-modal__step" data-fleet-step="result" hidden>
				<input type="hidden" name="fleet_outcome" id="fleet_outcome" value="">

				<div class="wd-fleet-message wd-fleet-message--ok" data-fleet-result="available" hidden role="status">
					<div class="wd-fleet-status wd-fleet-status--ok">
						<span class="wd-fleet-status__dot" aria-hidden="true"></span>
						<p class="wd-fleet-status__label"><?php esc_html_e( 'Available', 'doppler_lidar' ); ?></p>
					</div>
					<p class="wd-body"><?php esc_html_e( 'LiDARs are available for the checked conditions. Leave your details and we will contact you for a personalised discussion.', 'doppler_lidar' ); ?></p>
				</div>

				<div class="wd-fleet-message" data-fleet-result="custom" hidden role="status">
					<div class="wd-fleet-status wd-fleet-status--custom">
						<span class="material-symbols-outlined" aria-hidden="true">info</span>
						<p class="wd-fleet-status__label"><?php esc_html_e( 'Personalised solution', 'doppler_lidar' ); ?></p>
					</div>
					<p class="wd-body"><?php esc_html_e( 'A personalised solution can be found for requests above two units. Leave your details and we will come back with options.', 'doppler_lidar' ); ?></p>
				</div>

				<p class="wd-caption" data-fleet-summary></p>
				<div class="wd-field">
					<label class="wd-label" for="fleet_name"><?php esc_html_e( 'Name', 'doppler_lidar' ); ?></label>
					<input id="fleet_name" name="fleet_name" type="text" autocomplete="name">
				</div>
				<div class="wd-field">
					<label class="wd-label" for="fleet_email"><?php esc_html_e( 'Work email', 'doppler_lidar' ); ?></label>
					<input id="fleet_email" name="fleet_email" type="email" autocomplete="email">
				</div>
				<div class="wd-field">
					<label class="wd-label" for="fleet_company"><?php esc_html_e( 'Company', 'doppler_lidar' ); ?></label>
					<input id="fleet_company" name="fleet_company" type="text" autocomplete="organization">
				</div>
				<p class="wd-modal__error" data-fleet-error hidden></p>
				<div class="wd-modal__actions">
					<button class="wd-btn wd-btn--ghost" type="button" data-fleet-back><?php esc_html_e( 'Back', 'doppler_lidar' ); ?></button>
					<button class="wd-btn wd-btn--primary" type="submit" data-fleet-send><?php esc_html_e( 'Send enquiry', 'doppler_lidar' ); ?></button>
				</div>
			</div>

			<div class="wd-modal__step" data-fleet-step="sent" hidden>
				<p class="wd-body"><?php esc_html_e( 'Thank you. We received your enquiry and will be in touch.', 'doppler_lidar' ); ?></p>
				<div class="wd-modal__actions">
					<button class="wd-btn wd-btn--primary" type="button" data-wd-modal-close><?php esc_html_e( 'Close', 'doppler_lidar' ); ?></button>
				</div>
			</div>
		</form>
	</dialog>

</main>

<?php
get_footer();
