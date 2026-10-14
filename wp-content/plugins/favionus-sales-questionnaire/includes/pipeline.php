<?php
/**
 * Favionus Sales – pipeline CRM (kanban). Admin-only (manage_options), private data, no public output.
 * Data model: private CPTs fsp_company, fsp_contact, fsp_deal + post meta (no custom tables, no schema migrations).
 */
defined( 'ABSPATH' ) || exit;

function fsp_stages() {
	return array( 'contact' => '1. Contact', 'calificare' => '2. Calificare', 'oferta' => '3. Ofertă', 'negociere' => '4. Negociere', 'inchis' => '5. Închis' );
}
function fsp_outcomes() { return array( '' => '—', 'castigat' => 'Câștigat', 'pierdut' => 'Pierdut' ); }

/* Field definitions: key => [label, type] (type: text|email|url|textarea|number|date|company|contacts|stage|outcome|record) */
function fsp_fields( $type ) {
	$f = array(
		'fsp_company' => array(
			'vat'     => array( 'CUI / CIF (VAT)', 'text' ),
			'country' => array( 'Țară', 'text' ),
			'website' => array( 'Website', 'url' ),
			'notes'   => array( 'Note', 'textarea' ),
		),
		'fsp_contact' => array(
			'role'    => array( 'Funcție', 'text' ),
			'email'   => array( 'Email', 'email' ),
			'phone'   => array( 'Telefon', 'text' ),
			'company' => array( 'Companie', 'company' ),
			'notes'   => array( 'Note', 'textarea' ),
		),
		'fsp_deal' => array(
			'company'    => array( 'Companie', 'company' ),
			'contacts'   => array( 'Contacte', 'contacts' ),
			'stage'      => array( 'Etapă', 'stage' ),
			'outcome'    => array( 'Rezultat (la Închis)', 'outcome' ),
			'location'   => array( 'Amplasament', 'text' ),
			'site_type'  => array( 'Tip teren / amplasament', 'text' ),
			'height'     => array( 'Înălțime de măsurare', 'text' ),
			'duration'   => array( 'Durata campaniei', 'text' ),
			'start_date' => array( 'Data de început', 'date' ),
			'units'      => array( 'Unități LiDAR', 'number' ),
			'value_eur'  => array( 'Valoare estimată (EUR)', 'number' ),
			'next_step'  => array( 'Următorul pas', 'text' ),
			'next_date'  => array( 'Data următorului pas', 'date' ),
			'record'     => array( 'Fișă chestionar legată', 'record' ),
			'notes'      => array( 'Informații / note', 'textarea' ),
		),
	);
	return $f[ $type ];
}
function fsp_labels() { return array( 'fsp_company' => array( 'Companii', 'Companie' ), 'fsp_contact' => array( 'Contacte', 'Contact' ), 'fsp_deal' => array( 'Deal-uri', 'Deal' ) ); }
function fsp_m( $id, $k ) { return get_post_meta( $id, '_fsp_' . $k, true ); }

add_action( 'init', function () {
	foreach ( array_keys( fsp_labels() ) as $pt ) {
		register_post_type( $pt, array(
			'label' => fsp_labels()[ $pt ][0], 'public' => false, 'publicly_queryable' => false, 'exclude_from_search' => true,
			'show_ui' => false, 'show_in_menu' => false, 'show_in_nav_menus' => false, 'show_in_admin_bar' => false,
			'show_in_rest' => false, 'has_archive' => false, 'rewrite' => false, 'query_var' => false, 'can_export' => false,
			'supports' => array( 'title' ), 'map_meta_cap' => true, 'capabilities' => array( 'create_posts' => FSQ_CAP ),
		) );
	}
} );
add_filter( 'wp_sitemaps_post_types', function ( $t ) { foreach ( array_keys( fsp_labels() ) as $pt ) { unset( $t[ $pt ] ); } return $t; } );
add_filter( 'wpseo_sitemap_exclude_post_type', function ( $ex, $pt ) { return isset( fsp_labels()[ $pt ] ) ? true : $ex; }, 10, 2 );

/* ---------- Menu ---------- */
add_action( 'admin_menu', function () {
	$board = add_submenu_page( 'fsq-run', 'Pipeline vânzări', 'Pipeline', FSQ_CAP, 'fsp-board', 'fsp_render_board' );
	$hooks = array( $board );
	foreach ( array( 'fsp_deal' => 'fsp-deals', 'fsp_company' => 'fsp-companies', 'fsp_contact' => 'fsp-contacts' ) as $pt => $slug ) {
		$hooks[] = add_submenu_page( 'fsq-run', fsp_labels()[ $pt ][0], fsp_labels()[ $pt ][0], FSQ_CAP, $slug, function () use ( $pt, $slug ) { fsp_render_entity( $pt, $slug ); } );
	}
	foreach ( $hooks as $h ) { add_action( 'load-' . $h, 'fsq_noindex' ); }
	add_action( 'admin_enqueue_scripts', function ( $cur ) use ( $board, $hooks ) {
		if ( ! in_array( $cur, $hooks, true ) ) { return; }
		wp_enqueue_style( 'fsq', FSQ_URL . 'assets/fsq.css', array(), FSQ_VERSION );
		if ( $cur === $board ) {
			wp_enqueue_script( 'fsp-board', FSQ_URL . 'assets/pipeline.js', array(), FSQ_VERSION, true );
			wp_localize_script( 'fsp-board', 'FSP', array(
				'ajax' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'fsp_move' ),
				'edit' => admin_url( 'admin.php?page=fsp-deals&edit=' ), 'stages' => fsp_stages(), 'outcomes' => fsp_outcomes(),
				'deals' => fsp_board_data(),
			) );
		}
	} );
}, 20 );

function fsp_title( $id ) { return $id ? get_the_title( $id ) : ''; }
function fsp_all( $pt ) {
	return get_posts( array( 'post_type' => $pt, 'post_status' => 'private', 'numberposts' => 1000, 'orderby' => 'title', 'order' => 'ASC' ) );
}
function fsp_board_data() {
	$out = array();
	foreach ( get_posts( array( 'post_type' => 'fsp_deal', 'post_status' => 'private', 'numberposts' => 1000, 'orderby' => 'modified', 'order' => 'DESC' ) ) as $d ) {
		$contacts = array_filter( array_map( 'absint', (array) fsp_m( $d->ID, 'contacts' ) ) );
		$stage    = fsp_m( $d->ID, 'stage' );
		$out[] = array(
			'id' => $d->ID, 'title' => get_the_title( $d ), 'stage' => isset( fsp_stages()[ $stage ] ) ? $stage : 'contact',
			'outcome' => (string) fsp_m( $d->ID, 'outcome' ), 'company' => fsp_title( absint( fsp_m( $d->ID, 'company' ) ) ),
			'contact' => implode( ', ', array_map( 'fsp_title', $contacts ) ), 'value' => (float) fsp_m( $d->ID, 'value_eur' ),
			'next_step' => (string) fsp_m( $d->ID, 'next_step' ), 'next_date' => (string) fsp_m( $d->ID, 'next_date' ),
		);
	}
	return $out;
}

/* ---------- Stage log ---------- */
function fsp_set_stage( $id, $stage, $outcome = null ) {
	$old = fsp_m( $id, 'stage' );
	$old_out = fsp_m( $id, 'outcome' );
	if ( 'inchis' !== $stage ) { $outcome = ''; }
	if ( null === $outcome ) { $outcome = $old_out; }
	if ( $old === $stage && $old_out === $outcome ) { return; }
	update_post_meta( $id, '_fsp_stage', $stage );
	update_post_meta( $id, '_fsp_outcome', $outcome );
	$log   = json_decode( (string) fsp_m( $id, 'log' ), true );
	$log   = is_array( $log ) ? $log : array();
	$log[] = array( 't' => current_time( 'mysql' ), 'from' => (string) $old, 'to' => $stage, 'outcome' => $outcome, 'user' => wp_get_current_user()->user_login );
	update_post_meta( $id, '_fsp_log', wp_slash( wp_json_encode( $log, JSON_UNESCAPED_UNICODE ) ) );
}

/* ---------- AJAX: move card (logged-in admin only; no nopriv) ---------- */
add_action( 'wp_ajax_fsp_move', function () {
	if ( ! current_user_can( FSQ_CAP ) ) { wp_send_json_error( 'forbidden', 403 ); }
	check_ajax_referer( 'fsp_move', 'nonce' );
	$id      = absint( $_POST['id'] ?? 0 );
	$stage   = sanitize_key( $_POST['stage'] ?? '' );
	$outcome = sanitize_key( $_POST['outcome'] ?? '' );
	if ( get_post_type( $id ) !== 'fsp_deal' || ! isset( fsp_stages()[ $stage ] ) || ! isset( fsp_outcomes()[ $outcome ] ) ) { wp_send_json_error( 'bad request', 400 ); }
	fsp_set_stage( $id, $stage, $outcome );
	wp_send_json_success( array( 'stage' => $stage, 'outcome' => fsp_m( $id, 'outcome' ) ) );
} );

/* ---------- Save / delete entities (admin-post) ---------- */
add_action( 'admin_post_fsp_save', function () {
	if ( ! current_user_can( FSQ_CAP ) ) { wp_die( 'Forbidden', 403 ); }
	$pt = sanitize_key( $_POST['pt'] ?? '' );
	if ( ! isset( fsp_labels()[ $pt ] ) ) { wp_die( 'Bad type', 400 ); }
	$id = absint( $_POST['id'] ?? 0 );
	check_admin_referer( 'fsp_save_' . $pt . '_' . $id );
	if ( $id && get_post_type( $id ) !== $pt ) { wp_die( 'Bad id', 400 ); }
	$title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
	$args  = array( 'post_type' => $pt, 'post_status' => 'private', 'post_title' => $title ? $title : '(fără nume)' );
	if ( $id ) { $args['ID'] = $id; $id = wp_update_post( $args, true ); } else { $id = wp_insert_post( $args, true ); }
	if ( is_wp_error( $id ) ) { wp_die( esc_html( $id->get_error_message() ) ); }
	$in = wp_unslash( $_POST['f'] ?? array() );
	foreach ( fsp_fields( $pt ) as $k => $def ) {
		$v = $in[ $k ] ?? '';
		switch ( $def[1] ) {
			case 'stage':    continue 2; // handled below with log
			case 'outcome':  continue 2;
			case 'textarea': $v = sanitize_textarea_field( $v ); break;
			case 'email':    $v = sanitize_email( $v ); break;
			case 'url':      $v = esc_url_raw( $v ); break;
			case 'number':   $v = '' === $v ? '' : (string) (float) str_replace( ',', '.', $v ); break;
			case 'date':     $v = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : ''; break;
			case 'company':
			case 'record':   $v = absint( $v ); break;
			case 'contacts': $v = array_values( array_filter( array_map( 'absint', (array) $v ) ) ); break;
			default:         $v = sanitize_text_field( $v );
		}
		update_post_meta( $id, '_fsp_' . $k, $v );
	}
	if ( 'fsp_deal' === $pt ) {
		$stage   = sanitize_key( $in['stage'] ?? 'contact' );
		$outcome = sanitize_key( $in['outcome'] ?? '' );
		fsp_set_stage( $id, isset( fsp_stages()[ $stage ] ) ? $stage : 'contact', isset( fsp_outcomes()[ $outcome ] ) ? $outcome : '' );
	}
	$slug = array( 'fsp_deal' => 'fsp-deals', 'fsp_company' => 'fsp-companies', 'fsp_contact' => 'fsp-contacts' )[ $pt ];
	wp_safe_redirect( admin_url( 'admin.php?page=' . $slug . '&edit=' . $id . '&saved=1' ) );
	exit;
} );

add_action( 'admin_post_fsp_delete', function () {
	if ( ! current_user_can( FSQ_CAP ) ) { wp_die( 'Forbidden', 403 ); }
	$id = absint( $_GET['id'] ?? 0 );
	check_admin_referer( 'fsp_delete_' . $id );
	$pt = get_post_type( $id );
	if ( ! isset( fsp_labels()[ $pt ] ) ) { wp_die( 'Bad id', 400 ); }
	wp_delete_post( $id, true );
	$slug = array( 'fsp_deal' => 'fsp-deals', 'fsp_company' => 'fsp-companies', 'fsp_contact' => 'fsp-contacts' )[ $pt ];
	wp_safe_redirect( admin_url( 'admin.php?page=' . $slug . '&deleted=1' ) );
	exit;
} );

/* ---------- Create deal from questionnaire record ---------- */
function fsp_find_or_create( $pt, $title, $meta = array() ) {
	if ( '' === $title ) { return 0; }
	$found = get_posts( array( 'post_type' => $pt, 'post_status' => 'private', 'title' => $title, 'numberposts' => 1, 'fields' => 'ids' ) );
	if ( $found ) { return (int) $found[0]; }
	$id = wp_insert_post( array( 'post_type' => $pt, 'post_status' => 'private', 'post_title' => $title ) );
	foreach ( $meta as $k => $v ) { update_post_meta( $id, '_fsp_' . $k, $v ); }
	return (int) $id;
}
add_action( 'admin_post_fsp_from_record', function () {
	if ( ! current_user_can( FSQ_CAP ) ) { wp_die( 'Forbidden', 403 ); }
	$rid = absint( $_GET['record'] ?? 0 );
	check_admin_referer( 'fsp_from_record_' . $rid );
	if ( get_post_type( $rid ) !== FSQ_CPT ) { wp_die( 'Bad record', 400 ); }
	$ans = array();
	foreach ( (array) json_decode( (string) get_post_meta( $rid, '_fsq_answers', true ), true ) as $a ) {
		if ( ! empty( $a['field'] ) && isset( $a['answer'] ) && '—' !== $a['answer'] ) { $ans[ $a['field'] ] = $a['answer']; }
	}
	$company = sanitize_text_field( get_post_meta( $rid, '_fsq_company', true ) );
	$client  = sanitize_text_field( get_post_meta( $rid, '_fsq_client', true ) );
	$cid     = fsp_find_or_create( 'fsp_company', $company );
	$pid     = fsp_find_or_create( 'fsp_contact', $client, $cid ? array( 'company' => $cid ) : array() );
	$deal    = wp_insert_post( array( 'post_type' => 'fsp_deal', 'post_status' => 'private',
		'post_title' => trim( ( $company ? $company : 'Deal nou' ) . ( isset( $ans['location'] ) ? ' – ' . $ans['location'] : '' ) ) ) );
	$units = isset( $ans['num_sites'] ) ? (string) (int) $ans['num_sites'] : '';
	$map = array(
		'company' => $cid, 'contacts' => $pid ? array( $pid ) : array(), 'record' => $rid,
		'location' => $ans['location'] ?? '', 'site_type' => $ans['terrain'] ?? '', 'height' => $ans['max_height'] ?? ( $ans['hub_height'] ?? '' ),
		'duration' => $ans['duration'] ?? '', 'units' => '0' === $units ? '' : $units,
		'next_step' => $ans['next_step'] ?? '',
		'notes' => trim( "Creat din fișa chestionar #{$rid}.\n" . ( isset( $ans['start'] ) ? 'Start dorit: ' . $ans['start'] . "\n" : '' ) . ( isset( $ans['budget'] ) ? 'Buget: ' . $ans['budget'] . "\n" : '' ) . get_post_meta( $rid, '_fsq_notes', true ) ),
	);
	foreach ( $map as $k => $v ) { update_post_meta( $deal, '_fsp_' . $k, is_array( $v ) ? $v : sanitize_textarea_field( (string) $v ) ); }
	fsp_set_stage( $deal, 'calificare', '' );
	wp_safe_redirect( admin_url( 'admin.php?page=fsp-deals&edit=' . $deal . '&fromrecord=1' ) );
	exit;
} );

/* Shown on the questionnaire record view (called from the main plugin file). */
function fsp_record_actions( $rid ) {
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=fsp_from_record&record=' . $rid ), 'fsp_from_record_' . $rid );
	echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '">Creează deal din fișă</a>';
	$linked = get_posts( array( 'post_type' => 'fsp_deal', 'post_status' => 'private', 'numberposts' => 20, 'meta_key' => '_fsp_record', 'meta_value' => $rid ) );
	foreach ( $linked as $d ) {
		echo ' <a class="button" href="' . esc_url( admin_url( 'admin.php?page=fsp-deals&edit=' . $d->ID ) ) . '">Deal: ' . esc_html( get_the_title( $d ) ) . '</a>';
	}
	echo '</p>';
}

/* ---------- Board screen ---------- */
function fsp_render_board() {
	if ( ! current_user_can( FSQ_CAP ) ) { return; }
	echo '<div class="wrap fsp-wrap"><h1 class="wp-heading-inline">Pipeline vânzări</h1> ';
	echo '<a class="page-title-action" href="' . esc_url( admin_url( 'admin.php?page=fsp-deals&edit=new' ) ) . '">Deal nou</a> ';
	echo '<a class="page-title-action" href="' . esc_url( admin_url( 'admin.php?page=fsp-companies&edit=new' ) ) . '">Companie nouă</a> ';
	echo '<a class="page-title-action" href="' . esc_url( admin_url( 'admin.php?page=fsp-contacts&edit=new' ) ) . '">Contact nou</a>';
	echo '<p><input type="search" id="fsp-search" class="regular-text" placeholder="Caută (titlu, companie, contact, pas următor)…"> ';
	echo '<label><input type="checkbox" id="fsp-hide-lost"> Ascunde pierdute</label></p>';
	echo '<div id="fsp-board" class="fsp-board"><p>Se încarcă…</p></div></div>';
}

/* ---------- Generic list + edit screens ---------- */
function fsp_render_entity( $pt, $slug ) {
	if ( ! current_user_can( FSQ_CAP ) ) { return; }
	list( $plural, $single ) = fsp_labels()[ $pt ];
	$base = admin_url( 'admin.php?page=' . $slug );
	echo '<div class="wrap fsp-wrap">';
	$edit = $_GET['edit'] ?? '';
	if ( '' !== $edit ) {
		$id = 'new' === $edit ? 0 : absint( $edit );
		if ( $id && get_post_type( $id ) !== $pt ) { echo '<p>Nu există.</p></div>'; return; }
		fsp_render_form( $pt, $id, $single, $base );
		echo '</div>';
		return;
	}
	echo '<h1 class="wp-heading-inline">' . esc_html( $plural ) . '</h1> <a class="page-title-action" href="' . esc_url( $base . '&edit=new' ) . '">Adaugă</a>';
	if ( 'fsp_deal' === $pt ) { echo ' <a class="page-title-action" href="' . esc_url( admin_url( 'admin.php?page=fsp-board' ) ) . '">Vezi pipeline</a>'; }
	if ( isset( $_GET['deleted'] ) ) { echo '<div class="notice notice-success"><p>Șters.</p></div>'; }
	$items = fsp_all( $pt );
	if ( ! $items ) { echo '<p>Nimic încă.</p></div>'; return; }
	$cols = array( 'fsp_company' => array( 'vat', 'country' ), 'fsp_contact' => array( 'company', 'email', 'phone' ), 'fsp_deal' => array( 'company', 'stage', 'value_eur', 'next_step' ) )[ $pt ];
	$defs = fsp_fields( $pt );
	echo '<table class="widefat striped"><thead><tr><th>Nume</th>';
	foreach ( $cols as $c ) { echo '<th>' . esc_html( $defs[ $c ][0] ) . '</th>'; }
	echo '<th>Acțiuni</th></tr></thead><tbody>';
	foreach ( $items as $p ) {
		echo '<tr><td><a href="' . esc_url( $base . '&edit=' . $p->ID ) . '"><strong>' . esc_html( get_the_title( $p ) ) . '</strong></a></td>';
		foreach ( $cols as $c ) { echo '<td>' . esc_html( fsp_display( $p->ID, $c, $defs[ $c ][1] ) ) . '</td>'; }
		$del = wp_nonce_url( admin_url( 'admin-post.php?action=fsp_delete&id=' . $p->ID ), 'fsp_delete_' . $p->ID );
		echo '<td><a href="' . esc_url( $base . '&edit=' . $p->ID ) . '">Editează</a> | <a href="' . esc_url( $del ) . '" onclick="return confirm(\'Ștergi definitiv?\');">Șterge</a></td></tr>';
	}
	echo '</tbody></table></div>';
}

function fsp_display( $id, $k, $type ) {
	$v = fsp_m( $id, $k );
	if ( 'company' === $type ) { return fsp_title( absint( $v ) ); }
	if ( 'stage' === $type ) { $s = fsp_stages(); $o = fsp_outcomes(); $out = fsp_m( $id, 'outcome' ); return ( $s[ $v ] ?? $s['contact'] ) . ( $out ? ' – ' . $o[ $out ] : '' ); }
	if ( 'number' === $type && '' !== $v && 'value_eur' === $k ) { return number_format_i18n( (float) $v ) . ' EUR'; }
	return is_array( $v ) ? '' : (string) $v;
}

function fsp_render_form( $pt, $id, $single, $base ) {
	echo '<h1>' . esc_html( $id ? $single . ': ' . get_the_title( $id ) : $single . ' nou' ) . '</h1>';
	echo '<p><a href="' . esc_url( 'fsp_deal' === $pt ? admin_url( 'admin.php?page=fsp-board' ) : $base ) . '">&larr; Înapoi</a></p>';
	if ( isset( $_GET['saved'] ) ) { echo '<div class="notice notice-success"><p>Salvat.</p></div>'; }
	if ( isset( $_GET['fromrecord'] ) ) { echo '<div class="notice notice-info"><p>Deal creat din fișa chestionarului. Verifică și completează câmpurile, apoi salvează.</p></div>'; }
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'fsp_save_' . $pt . '_' . $id );
	echo '<input type="hidden" name="action" value="fsp_save"><input type="hidden" name="pt" value="' . esc_attr( $pt ) . '"><input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
	echo '<table class="form-table"><tr><th><label for="fsp-title">' . ( 'fsp_deal' === $pt ? 'Titlu deal / proiect' : 'Nume' ) . '</label></th><td><input required id="fsp-title" name="title" class="regular-text" value="' . esc_attr( $id ? get_the_title( $id ) : '' ) . '"></td></tr>';
	foreach ( fsp_fields( $pt ) as $k => $def ) {
		$v    = $id ? fsp_m( $id, $k ) : ( 'stage' === $def[1] ? 'contact' : '' );
		$name = 'f[' . $k . ']';
		echo '<tr><th><label>' . esc_html( $def[0] ) . '</label></th><td>';
		switch ( $def[1] ) {
			case 'textarea': echo '<textarea name="' . esc_attr( $name ) . '" rows="5" class="large-text">' . esc_textarea( $v ) . '</textarea>'; break;
			case 'stage':
			case 'outcome':
				echo '<select name="' . esc_attr( $name ) . '">';
				foreach ( ( 'stage' === $def[1] ? fsp_stages() : fsp_outcomes() ) as $sk => $sl ) { echo '<option value="' . esc_attr( $sk ) . '"' . selected( $v, $sk, false ) . '>' . esc_html( $sl ) . '</option>'; }
				echo '</select>'; break;
			case 'company':
			case 'record':
				$opts = 'company' === $def[1] ? fsp_all( 'fsp_company' ) : get_posts( array( 'post_type' => FSQ_CPT, 'post_status' => 'private', 'numberposts' => 200 ) );
				echo '<select name="' . esc_attr( $name ) . '"><option value="0">—</option>';
				foreach ( $opts as $o ) { echo '<option value="' . esc_attr( $o->ID ) . '"' . selected( absint( $v ), $o->ID, false ) . '>' . esc_html( get_the_title( $o ) ) . '</option>'; }
				echo '</select>';
				if ( 'record' === $def[1] && $v ) { echo ' <a href="' . esc_url( admin_url( 'admin.php?page=fsq-records&view=' . absint( $v ) ) ) . '">Deschide fișa</a>'; }
				break;
			case 'contacts':
				$sel = array_map( 'absint', (array) $v );
				echo '<select name="' . esc_attr( $name ) . '[]" multiple size="5" style="min-width:300px">';
				foreach ( fsp_all( 'fsp_contact' ) as $o ) {
					$co = fsp_title( absint( fsp_m( $o->ID, 'company' ) ) );
					echo '<option value="' . esc_attr( $o->ID ) . '"' . ( in_array( $o->ID, $sel, true ) ? ' selected' : '' ) . '>' . esc_html( get_the_title( $o ) . ( $co ? ' (' . $co . ')' : '' ) ) . '</option>';
				}
				echo '</select><p class="description">Ctrl/Cmd + click pentru mai multe.</p>'; break;
			default:
				$t = array( 'email' => 'email', 'url' => 'url', 'number' => 'number', 'date' => 'date' )[ $def[1] ] ?? 'text';
				echo '<input type="' . esc_attr( $t ) . '"' . ( 'number' === $t ? ' step="any"' : '' ) . ' name="' . esc_attr( $name ) . '" class="regular-text" value="' . esc_attr( is_array( $v ) ? '' : $v ) . '">';
		}
		echo '</td></tr>';
	}
	echo '</table>';
	submit_button( 'Salvează' );
	echo '</form>';
	if ( 'fsp_deal' === $pt && $id ) {
		$log = json_decode( (string) fsp_m( $id, 'log' ), true );
		echo '<h2>Istoric etape</h2>';
		if ( ! $log ) { echo '<p>—</p>'; } else {
			$s = fsp_stages(); $o = fsp_outcomes();
			echo '<table class="widefat striped"><thead><tr><th>Data</th><th>Din</th><th>În</th><th>Utilizator</th></tr></thead><tbody>';
			foreach ( array_reverse( $log ) as $e ) {
				echo '<tr><td>' . esc_html( $e['t'] ?? '' ) . '</td><td>' . esc_html( $s[ $e['from'] ?? '' ] ?? '—' ) . '</td><td>' . esc_html( ( $s[ $e['to'] ?? '' ] ?? '' ) . ( ! empty( $e['outcome'] ) ? ' – ' . ( $o[ $e['outcome'] ] ?? '' ) : '' ) ) . '</td><td>' . esc_html( $e['user'] ?? '' ) . '</td></tr>';
			}
			echo '</tbody></table>';
		}
	}
	if ( 'fsp_company' === $pt && $id ) {
		echo '<h2>Contacte și deal-uri</h2><ul>';
		foreach ( array( 'fsp_contact' => 'fsp-contacts', 'fsp_deal' => 'fsp-deals' ) as $rpt => $rslug ) {
			foreach ( get_posts( array( 'post_type' => $rpt, 'post_status' => 'private', 'numberposts' => 100, 'meta_key' => '_fsp_company', 'meta_value' => $id ) ) as $r ) {
				echo '<li>' . esc_html( fsp_labels()[ $rpt ][1] ) . ': <a href="' . esc_url( admin_url( 'admin.php?page=' . $rslug . '&edit=' . $r->ID ) ) . '">' . esc_html( get_the_title( $r ) ) . '</a></li>';
			}
		}
		echo '</ul>';
	}
}
