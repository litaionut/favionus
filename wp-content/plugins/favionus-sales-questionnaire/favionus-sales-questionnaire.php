<?php
/**
 * Plugin Name: Favionus Sales
 * Description: Chestionar de vânzări ghidat + pipeline CRM (kanban), doar pentru administratori (wp-admin). Nu are nicio ieșire publică.
 * Version: 0.2.0
 * Author: Favionus
 * Requires PHP: 7.4
 * License: Proprietary
 */

defined( 'ABSPATH' ) || exit;

define( 'FSQ_VERSION', '0.2.0' );
define( 'FSQ_DIR', plugin_dir_path( __FILE__ ) );
define( 'FSQ_URL', plugin_dir_url( __FILE__ ) );
define( 'FSQ_CPT', 'favionus_sales_rec' );
define( 'FSQ_CAP', 'manage_options' );

/* ---------- Private record type ---------- */
add_action( 'init', function () {
	register_post_type( FSQ_CPT, array(
		'label'               => 'Sales records',
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => false,
		'show_in_menu'        => false,
		'show_in_nav_menus'   => false,
		'show_in_admin_bar'   => false,
		'show_in_rest'        => false,
		'has_archive'         => false,
		'rewrite'             => false,
		'query_var'           => false,
		'can_export'          => false,
		'supports'            => array( 'title' ),
		'capability_type'     => 'post',
		'capabilities'        => array( 'create_posts' => FSQ_CAP ),
		'map_meta_cap'        => true,
	) );
} );

/* Never let a record leak into sitemaps. */
add_filter( 'wp_sitemaps_post_types', function ( $types ) { unset( $types[ FSQ_CPT ] ); return $types; } );
add_filter( 'wpseo_sitemap_exclude_post_type', function ( $ex, $pt ) { return $pt === FSQ_CPT ? true : $ex; }, 10, 2 );

/* ---------- Admin menu ---------- */
add_action( 'admin_menu', function () {
	$hook = add_menu_page( 'Chestionar vânzări', 'Favionus Sales', FSQ_CAP, 'fsq-run', 'fsq_render_run', 'dashicons-clipboard', 58 );
	add_submenu_page( 'fsq-run', 'Chestionar vânzări', 'Chestionar', FSQ_CAP, 'fsq-run', 'fsq_render_run' );
	$list = add_submenu_page( 'fsq-run', 'Fișe salvate', 'Fișe salvate', FSQ_CAP, 'fsq-records', 'fsq_render_records' );
	foreach ( array( $hook, $list ) as $h ) {
		add_action( 'load-' . $h, 'fsq_noindex' );
	}
	add_action( 'admin_enqueue_scripts', function ( $current ) use ( $hook, $list ) {
		if ( $current !== $hook && $current !== $list ) { return; }
		wp_enqueue_style( 'fsq', FSQ_URL . 'assets/fsq.css', array(), FSQ_VERSION );
		if ( $current === $hook ) {
			wp_enqueue_script( 'fsq', FSQ_URL . 'assets/fsq.js', array(), FSQ_VERSION, true );
			wp_localize_script( 'fsq', 'FSQ', array(
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'fsq_save' ),
				'tree'  => fsq_load_tree(),
				'lang'  => 'ro', // Romanian UI; nodes fall back to text_en when text_ro is missing.,
			) );
		}
	} );
} );

function fsq_noindex() {
	if ( ! current_user_can( FSQ_CAP ) ) { wp_die( 'Forbidden', 403 ); }
	if ( ! headers_sent() ) {
		header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
		nocache_headers();
	}
	add_action( 'admin_head', function () { echo '<meta name="robots" content="noindex,nofollow">' . "\n"; } );
}


/* ---------- Tree ---------- */
function fsq_load_tree() {
	$file = FSQ_DIR . 'question-tree.json';
	if ( ! is_readable( $file ) ) { return null; }
	$data = json_decode( (string) file_get_contents( $file ), true );
	return is_array( $data ) ? $data : null;
}

/* ---------- Save (admin-ajax, logged-in only; no nopriv hook) ---------- */
add_action( 'wp_ajax_fsq_save', function () {
	if ( ! current_user_can( FSQ_CAP ) ) { wp_send_json_error( 'forbidden', 403 ); }
	check_ajax_referer( 'fsq_save', 'nonce' );

	$client  = sanitize_text_field( wp_unslash( $_POST['client'] ?? '' ) );
	$company = sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) );
	$notes   = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
	$summary = sanitize_textarea_field( wp_unslash( $_POST['summary'] ?? '' ) );
	$offer   = sanitize_textarea_field( wp_unslash( $_POST['offer'] ?? '' ) );
	$raw     = json_decode( wp_unslash( $_POST['answers'] ?? '[]' ), true );
	if ( ! is_array( $raw ) ) { wp_send_json_error( 'bad answers', 400 ); }

	$answers = array();
	foreach ( array_slice( $raw, 0, 500 ) as $a ) {
		if ( ! is_array( $a ) ) { continue; }
		$answers[] = array(
			'id'       => sanitize_key( $a['id'] ?? '' ),
			'section'  => sanitize_text_field( $a['section'] ?? '' ),
			'field'    => sanitize_key( $a['field'] ?? '' ),
			'question' => sanitize_text_field( $a['question'] ?? '' ),
			'answer'   => sanitize_textarea_field( (string) ( $a['answer'] ?? '' ) ),
			'value'    => sanitize_text_field( (string) ( $a['value'] ?? '' ) ),
		);
	}

	$title = trim( $company . ' – ' . $client, ' –' );
	$id = wp_insert_post( array(
		'post_type'    => FSQ_CPT,
		'post_status'  => 'private',
		'post_title'   => ( $title ? $title : 'Fișă' ) . ' (' . current_time( 'Y-m-d H:i' ) . ')',
		'post_content' => $summary,
	), true );
	if ( is_wp_error( $id ) ) { wp_send_json_error( $id->get_error_message(), 500 ); }

	update_post_meta( $id, '_fsq_client', $client );
	update_post_meta( $id, '_fsq_company', $company );
	update_post_meta( $id, '_fsq_notes', $notes );
	update_post_meta( $id, '_fsq_offer', $offer );
	update_post_meta( $id, '_fsq_answers', wp_slash( wp_json_encode( $answers, JSON_UNESCAPED_UNICODE ) ) );
	wp_send_json_success( array( 'id' => $id, 'url' => admin_url( 'admin.php?page=fsq-records&view=' . $id ) ) );
} );

/* ---------- Delete (admin-post) ---------- */
add_action( 'admin_post_fsq_delete', function () {
	if ( ! current_user_can( FSQ_CAP ) ) { wp_die( 'Forbidden', 403 ); }
	$id = absint( $_GET['id'] ?? 0 );
	check_admin_referer( 'fsq_delete_' . $id );
	if ( $id && get_post_type( $id ) === FSQ_CPT ) { wp_delete_post( $id, true ); }
	wp_safe_redirect( admin_url( 'admin.php?page=fsq-records&deleted=1' ) );
	exit;
} );

/* ---------- Screens ---------- */
function fsq_render_run() {
	if ( ! current_user_can( FSQ_CAP ) ) { return; }
	echo '<div class="wrap fsq-wrap"><h1>Chestionar vânzări <small>(Sales questionnaire)</small></h1>';
	if ( ! fsq_load_tree() ) {
		echo '<div class="notice notice-error"><p>question-tree.json lipsește sau nu e JSON valid.</p></div></div>';
		return;
	}
	echo '<div id="fsq-app" aria-live="polite"><p>Se încarcă…</p></div></div>';
}

function fsq_render_records() {
	if ( ! current_user_can( FSQ_CAP ) ) { return; }
	echo '<div class="wrap fsq-wrap">';
	$view = absint( $_GET['view'] ?? 0 );
	if ( $view && get_post_type( $view ) === FSQ_CPT ) {
		$p       = get_post( $view );
		$answers = json_decode( (string) get_post_meta( $view, '_fsq_answers', true ), true );
		echo '<h1>' . esc_html( get_the_title( $p ) ) . '</h1>';
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=fsq-records' ) ) . '">&larr; Înapoi la listă</a></p>';
		if ( function_exists( 'fsp_record_actions' ) ) { fsp_record_actions( $view ); }
		echo '<table class="widefat striped"><tbody>';
		echo '<tr><th>Client</th><td>' . esc_html( get_post_meta( $view, '_fsq_client', true ) ) . '</td></tr>';
		echo '<tr><th>Companie</th><td>' . esc_html( get_post_meta( $view, '_fsq_company', true ) ) . '</td></tr>';
		echo '<tr><th>Note întâlnire</th><td>' . nl2br( esc_html( get_post_meta( $view, '_fsq_notes', true ) ) ) . '</td></tr>';
		echo '</tbody></table><h2>Răspunsuri</h2><table class="widefat striped"><tbody>';
		foreach ( (array) $answers as $a ) {
			echo '<tr><th>' . esc_html( $a['question'] ?? '' ) . '</th><td>' . nl2br( esc_html( $a['answer'] ?? '' ) ) . '</td></tr>';
		}
		echo '</tbody></table><h2>Scope pentru client (fără marcaje [confirm])</h2><textarea class="large-text code" rows="10" readonly>' . esc_textarea( get_post_meta( $view, '_fsq_offer', true ) ) . '</textarea>';
		echo '<h2>Fișă internă</h2><textarea class="large-text code" rows="14" readonly>' . esc_textarea( $p->post_content ) . '</textarea></div>';
		return;
	}
	echo '<h1>Fișe salvate</h1>';
	if ( isset( $_GET['deleted'] ) ) { echo '<div class="notice notice-success"><p>Fișa a fost ștearsă.</p></div>'; }
	$q = get_posts( array( 'post_type' => FSQ_CPT, 'post_status' => 'private', 'numberposts' => 200, 'orderby' => 'date', 'order' => 'DESC' ) );
	if ( ! $q ) { echo '<p>Nu există fișe salvate.</p></div>'; return; }
	echo '<table class="widefat striped"><thead><tr><th>Fișă</th><th>Data</th><th>Acțiuni</th></tr></thead><tbody>';
	foreach ( $q as $p ) {
		$del = wp_nonce_url( admin_url( 'admin-post.php?action=fsq_delete&id=' . $p->ID ), 'fsq_delete_' . $p->ID );
		echo '<tr><td>' . esc_html( get_the_title( $p ) ) . '</td><td>' . esc_html( get_the_date( 'Y-m-d H:i', $p ) ) . '</td><td>'
			. '<a href="' . esc_url( admin_url( 'admin.php?page=fsq-records&view=' . $p->ID ) ) . '">Vezi</a> | '
			. '<a href="' . esc_url( $del ) . '" onclick="return confirm(\'Ștergi definitiv fișa?\');">Șterge</a></td></tr>';
	}
	echo '</tbody></table></div>';
}

require_once FSQ_DIR . 'includes/pipeline.php';
