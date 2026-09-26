<?php
/**
 * doppler_lidar Theme Customizer
 *
 * @package doppler_lidar
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function doppler_lidar_customize_register( $wp_customize ) {
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->selective_refresh->add_partial(
			'blogname',
			array(
				'selector'        => '.site-title a',
				'render_callback' => 'doppler_lidar_customize_partial_blogname',
			)
		);
		$wp_customize->selective_refresh->add_partial(
			'blogdescription',
			array(
				'selector'        => '.site-description',
				'render_callback' => 'doppler_lidar_customize_partial_blogdescription',
			)
		);
	}

	$wp_customize->add_section(
		'doppler_lidar_design',
		array(
			'title'    => __( 'Design', 'doppler_lidar' ),
			'priority' => 30,
		)
	);

	$wp_customize->add_setting(
		'doppler_lidar_skin',
		array(
			'default'           => 'vane',
			'sanitize_callback' => 'doppler_lidar_sanitize_skin',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'doppler_lidar_skin',
		array(
			'label'       => __( 'Skin', 'doppler_lidar' ),
			'description' => __( 'Switch between the Vane red-grid look and the Classic terracotta look.', 'doppler_lidar' ),
			'section'     => 'doppler_lidar_design',
			'type'        => 'select',
			'choices'     => array(
				'vane'    => __( 'Vane (red grid)', 'doppler_lidar' ),
				'classic' => __( 'Classic (terracotta)', 'doppler_lidar' ),
			),
		)
	);
}
add_action( 'customize_register', 'doppler_lidar_customize_register' );

/**
 * Render the site title for the selective refresh partial.
 *
 * @return void
 */
function doppler_lidar_customize_partial_blogname() {
	bloginfo( 'name' );
}

/**
 * Render the site tagline for the selective refresh partial.
 *
 * @return void
 */
function doppler_lidar_customize_partial_blogdescription() {
	bloginfo( 'description' );
}

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function doppler_lidar_customize_preview_js() {
	wp_enqueue_script( 'doppler_lidar-customizer', get_template_directory_uri() . '/js/customizer.js', array( 'customize-preview' ), _S_VERSION, true );
}
add_action( 'customize_preview_init', 'doppler_lidar_customize_preview_js' );
