<?php
/**
 * Favionus Sales – pipeline CRM (kanban) v2. Admin-only (manage_options), private data, no public output.
 * Data: private CPTs fsp_company, fsp_contact, fsp_deal (= card = one contact/lead) with post meta `_fsp_*`.
 * Columns ("stages") are stored in option `fsp_stages`, editable in Favionus Sales → Setări pipeline.
 */
defined( 'ABSPATH' ) || exit;

define( 'FSP_DB', 2 );

/* ---------- Stages ---------- */
/* kind: backlog (imported, not contacted) | open | won | lost. Only "open" columns can be deleted (when empty). */
function fsp_default_stages() {
	return array(
		array( 'key' => 'backlog',    'label' => 'Backlog',               'kind' => 'backlog', 'enabled' => 1 ),
		array( 'key' => 'contactati', 'label' => 'Contactați (abordați)', 'kind' => 'open',    'enabled' => 1 ),
		array( 'key' => 'raspuns',    'label' => 'Au răspuns',            'kind' => 'open',    'enabled' => 1 ),
		array( 'key' => 'meeting',    'label' => 'Meeting avut',          'kind' => 'open',    'enabled' => 1 ),
		array( 'key' => 'followup',   'label' => 'Follow-up',             'kind' => 'open',    'enabled' => 1 ),
		array( 'key' => 'vandut',     'label' => 'Vândut',                'kind' => 'won',     'enabled' => 1 ),
		array( 'key' => 'pierdut',    'label' => 'Pierdut',               'kind' => 'lost',    'enabled' => 1 ),
	);
}
function fsp_stage_list( $only_enabled = false ) {
	$s = get_option( 'fsp_stages' );
	if ( ! is_array( $s ) || ! $s ) { $s = fsp_default_stages(); }
	return $only_enabled ? array_values( array_filter( $s, function ( $x ) { return ! empty( $x['enabled'] ); } ) ) : $s;
}
/** key => label of enabled columns, in order. */
function fsp_stages() { $o = array(); foreach ( fsp_stage_list( true ) as $x ) { $o[ $x['key'] ] = $x['label']; } return $o; }
function fsp_stage_kind( $key ) { foreach ( fsp_stage_list() as $x ) { if ( $x['key'] === $key ) { return $x['kind']; } } return ''; }
function fsp_stage_by_kind( $kind ) { foreach ( fsp_stage_list() as $x ) { if ( $x['kind'] === $kind ) { return $x['key']; } } return ''; }
function fsp_first_open() { foreach ( fsp_stage_list( true ) as $x ) { if ( 'open' === $x['kind'] ) { return $x['key']; } } return fsp_stage_by_kind( 'backlog' ); }

function fsp_project_types() { return array( '' => '—', 'greenfield' => 'Greenfield', 'met_mast' => 'Met mast', 'repowering' => 'Repowering', 'site_investigation' => 'Site investigation' ); }
function fsp_lost_reasons() { return array( 'pret' => 'Preț', 'timing' => 'Timing / amânat', 'concurent' => 'A ales un concurent', 'fara_raspuns' => 'Fără răspuns', 'nu_e_nevoie' => 'Nu mai e nevoie', 'altul' => 'Altul' ); }

/* Field definitions: key => [label, type, group]. */
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
			'contact_name' => array( 'Nume contact', 'text', 'Contact' ),
			'company_name' => array( 'Companie', 'text', 'Contact' ),
			'role'         => array( 'Funcție', 'text', 'Contact' ),
			'email'        => array( 'Email', 'email', 'Contact' ),
			'phone'        => array( 'Telefon', 'text', 'Contact' ),
			'website'      => array( 'Website companie', 'url', 'Contact' ),
			'project_name' => array( 'Nume proiect', 'text', 'Proiect' ),
			'country'      => array( 'Țară', 'text', 'Proiect' ),
			'location'     => array( 'Locație (județ / coordonate)', 'text', 'Proiect' ),
			'mw'           => array( 'Putere (MW)', 'number', 'Proiect' ),
			'project_type' => array( 'Tip proiect', 'ptype', 'Proiect' ),
			'notes'        => array( 'Descriere / note', 'textarea', 'Proiect' ),
			'stage'        => array( 'Coloană', 'stage', 'Vânzare' ),
			'source'       => array( 'Sursă lead', 'source', 'Vânzare' ),
			'value_eur'    => array( 'Valoare estimată ofertă (EUR)', 'number', 'Vânzare' ),
			'next_step'    => array( 'Următorul pas', 'text', 'Vânzare' ),
			'next_date'    => array( 'Dată follow-up', 'date', 'Vânzare' ),
			'contacts'     => array( 'Contacte suplimentare', 'contacts', 'Legături' ),
			'site_type'    => array( 'Tip teren', 'text', 'Detalii campanie' ),
			'height'       => array( 'Înălțime de măsurare', 'text', 'Detalii campanie' ),
			'duration'     => array( 'Durata campaniei', 'text', 'Detalii campanie' ),
			'start_date'   => array( 'Data de început', 'date', 'Detalii campanie' ),
			'units'        => array( 'Unități LiDAR', 'number', 'Detalii campanie' ),
		),
	);
	return $f[ $type ];
}
function fsp_labels() { return array( 'fsp_company' => array( 'Companii', 'Companie' ), 'fsp_contact' => array( 'Contacte', 'Contact' ), 'fsp_deal' => array( 'Carduri (lead-uri)', 'Card' ) ); }
function fsp_slug( $pt ) { return array( 'fsp_deal' => 'fsp-deals', 'fsp_company' => 'fsp-companies', 'fsp_contact' => 'fsp-contacts' )[ $pt ]; }
function fsp_m( $id, $k ) { return get_post_meta( $id, '_fsp_' . $k, true ); }
function fsp_title( $id ) { return $id ? fsq_title( $id ) : ''; }
function fsp_all( $pt, $n = 2000 ) {
	return get_posts( array( 'post_type' => $pt, 'post_status' => 'private', 'numberposts' => $n, 'orderby' => 'title', 'order' => 'ASC' ) );
}

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

/* ---------- Timeline ---------- */
/* Entry: {t, type: stage|note|record|import|migrate, from, to, text, user} */
function fsp_log( $id, $entry ) {
	$log   = json_decode( (string) fsp_m( $id, 'log' ), true );
	$log   = is_array( $log ) ? $log : array();
	$log[] = array_merge( array( 't' => current_time( 'mysql' ), 'type' => 'note', 'from' => '', 'to' => '', 'text' => '', 'user' => wp_get_current_user()->user_login ), $entry );
	update_post_meta( $id, '_fsp_log', wp_slash( wp_json_encode( $log, JSON_UNESCAPED_UNICODE ) ) );
}
function fsp_set_stage( $id, $stage, $text = '' ) {
	$old = (string) fsp_m( $id, 'stage' );
	if ( $old === $stage ) { if ( $text ) { fsp_log( $id, array( 'type' => 'note', 'text' => $text ) ); } return; }
	update_post_meta( $id, '_fsp_stage', $stage );
	fsp_log( $id, array( 'type' => 'stage', 'from' => $old, 'to' => $stage, 'text' => $text ) );
}

/* ---------- Migration from v1 stage keys (contact/calificare/oferta/negociere/inchis+outcome) ---------- */
add_action( 'admin_init', function () {
	if ( (int) get_option( 'fsp_db', 1 ) >= FSP_DB || ! current_user_can( FSQ_CAP ) ) { return; }
	if ( ! get_option( 'fsp_stages' ) ) { update_option( 'fsp_stages', fsp_default_stages(), false ); }
	$map = array( 'contact' => 'contactati', 'calificare' => 'raspuns', 'oferta' => 'followup', 'negociere' => 'followup' );
	$deals = get_posts( array( 'post_type' => 'fsp_deal', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );
	foreach ( $deals as $id ) {
		$old = (string) fsp_m( $id, 'stage' );
		$new = $old;
		if ( 'inchis' === $old ) { $new = 'pierdut' === fsp_m( $id, 'outcome' ) ? 'pierdut' : 'vandut'; }
		elseif ( isset( $map[ $old ] ) ) { $new = $map[ $old ]; }
		elseif ( ! fsp_stage_kind( $old ) ) { $new = 'contactati'; }
		if ( $new !== $old ) {
			update_post_meta( $id, '_fsp_stage', $new );
			fsp_log( $id, array( 'type' => 'migrate', 'from' => $old, 'to' => $new, 'text' => 'Migrare coloane v2', 'user' => 'system' ) );
		}
		$rec = absint( fsp_m( $id, 'record' ) );
		if ( $rec ) { fsp_link_record( $id, $rec, false ); delete_post_meta( $id, '_fsp_record' ); }
		// fill card contact fields from linked entities
		$cid = absint( fsp_m( $id, 'company' ) );
		$pids = array_filter( array_map( 'absint', (array) fsp_m( $id, 'contacts' ) ) );
		if ( ! fsp_m( $id, 'company_name' ) && $cid ) { update_post_meta( $id, '_fsp_company_name', fsq_title( $cid ) ); update_post_meta( $id, '_fsp_country', fsp_m( $cid, 'country' ) ); }
		if ( ! fsp_m( $id, 'contact_name' ) && $pids ) {
			$p = array_shift( $pids );
			update_post_meta( $id, '_fsp_contact', $p );
			update_post_meta( $id, '_fsp_contacts', array_values( $pids ) );
			update_post_meta( $id, '_fsp_contact_name', fsq_title( $p ) );
			foreach ( array( 'role', 'email', 'phone' ) as $k ) { update_post_meta( $id, '_fsp_' . $k, fsp_m( $p, $k ) ); }
		}
	}
	update_option( 'fsp_db', FSP_DB, false );
} );

/* ---------- Entity sync: card -> company + primary contact ---------- */
function fsp_find_company( $name ) {
	if ( '' === $name ) { return 0; }
	foreach ( get_posts( array( 'post_type' => 'fsp_company', 'post_status' => 'private', 'title' => $name, 'numberposts' => 1, 'fields' => 'ids' ) ) as $i ) { return (int) $i; }
	return 0;
}
function fsp_find_contact( $email, $name, $company_id ) {
	if ( $email ) {
		$r = get_posts( array( 'post_type' => 'fsp_contact', 'post_status' => 'private', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_fsp_email', 'meta_value' => $email ) );
		if ( $r ) { return (int) $r[0]; }
	}
	if ( $name ) {
		foreach ( get_posts( array( 'post_type' => 'fsp_contact', 'post_status' => 'private', 'title' => $name, 'numberposts' => 20, 'fields' => 'ids' ) ) as $i ) {
			if ( absint( fsp_m( $i, 'company' ) ) === $company_id ) { return (int) $i; }
		}
	}
	return 0;
}
function fsp_sync_entities( $deal ) {
	$cname = (string) fsp_m( $deal, 'company_name' );
	$cid   = fsp_find_company( $cname );
	if ( $cname && ! $cid ) { $cid = (int) wp_insert_post( array( 'post_type' => 'fsp_company', 'post_status' => 'private', 'post_title' => $cname ) ); }
	if ( $cid ) {
		foreach ( array( 'country', 'website' ) as $k ) { $v = fsp_m( $deal, $k ); if ( $v && ! fsp_m( $cid, $k ) ) { update_post_meta( $cid, '_fsp_' . $k, $v ); } }
	}
	update_post_meta( $deal, '_fsp_company', $cid );
	$pname = (string) fsp_m( $deal, 'contact_name' );
	$email = (string) fsp_m( $deal, 'email' );
	$pid   = absint( fsp_m( $deal, 'contact' ) );
	if ( ! $pid || get_post_type( $pid ) !== 'fsp_contact' ) { $pid = fsp_find_contact( $email, $pname, $cid ); }
	if ( ! $pid && ( $pname || $email ) ) { $pid = (int) wp_insert_post( array( 'post_type' => 'fsp_contact', 'post_status' => 'private', 'post_title' => $pname ? $pname : $email ) ); }
	if ( $pid ) {
		if ( $pname && fsq_title( $pid ) !== $pname ) { wp_update_post( array( 'ID' => $pid, 'post_title' => $pname ) ); }
		foreach ( array( 'role', 'email', 'phone' ) as $k ) { update_post_meta( $pid, '_fsp_' . $k, fsp_m( $deal, $k ) ); }
		if ( $cid ) { update_post_meta( $pid, '_fsp_company', $cid ); }
	}
	update_post_meta( $deal, '_fsp_contact', $pid );
	$title = $pname ? $pname : ( $cname ? $cname : ( $email ? $email : '(fără nume)' ) );
	if ( fsq_title( $deal ) !== $title ) { wp_update_post( array( 'ID' => $deal, 'post_title' => $title ) ); }
}

/* ---------- Questionnaire links ---------- */
function fsp_record_ids( $deal ) { return array_values( array_filter( array_map( 'absint', (array) fsp_m( $deal, 'records' ) ) ) ); }
function fsp_link_record( $deal, $rec, $log = true ) {
	$ids = fsp_record_ids( $deal );
	if ( ! in_array( $rec, $ids, true ) ) { $ids[] = $rec; update_post_meta( $deal, '_fsp_records', $ids ); }
	update_post_meta( $rec, '_fsq_deal', $deal );
	if ( $log ) { fsp_log( $deal, array( 'type' => 'record', 'text' => 'Fișă chestionar salvată: ' . fsq_title( $rec ), 'to' => (string) $rec ) ); }
}
/** Prefill data for the questionnaire when opened from a card (?deal=ID). */
function fsp_questionnaire_prefill( $deal ) {
	if ( ! $deal || get_post_type( $deal ) !== 'fsp_deal' || ! current_user_can( FSQ_CAP ) ) { return null; }
	$loc = trim( implode( ', ', array_filter( array( fsp_m( $deal, 'location' ), fsp_m( $deal, 'country' ) ) ) ) );
	return array(
		'deal' => $deal, 'title' => fsq_title( $deal ),
		'client' => (string) fsp_m( $deal, 'contact_name' ), 'company' => (string) fsp_m( $deal, 'company_name' ),
		'fields' => array( 'location' => $loc ),
	);
}

/* ---------- Board data + filters ---------- */
function fsp_card( $d ) {
	$id = $d->ID;
	$recs = array();
	foreach ( fsp_record_ids( $id ) as $r ) {
		if ( get_post_type( $r ) !== FSQ_CPT ) { continue; }
		$recs[] = array( 'id' => $r, 'title' => fsq_title( $r ), 'offer' => (string) get_post_meta( $r, '_fsq_offer', true ),
			'url' => admin_url( 'admin.php?page=fsq-records&view=' . $r ) );
	}
	$stage = (string) fsp_m( $id, 'stage' );
	if ( ! fsp_stage_kind( $stage ) ) { $stage = fsp_first_open(); }
	$c = array( 'id' => $id, 'title' => fsq_title( $d ), 'stage' => $stage, 'records' => $recs, 'modified' => get_the_modified_date( 'Y-m-d', $d ) );
	foreach ( array( 'contact_name', 'company_name', 'role', 'email', 'phone', 'website', 'project_name', 'country', 'location', 'mw', 'project_type', 'notes', 'source', 'value_eur', 'next_step', 'next_date' ) as $k ) {
		$c[ $k ] = (string) fsp_m( $id, $k );
	}
	$c['value_eur'] = (float) $c['value_eur'];
	$k = fsp_stage_kind( $stage );
	$c['overdue'] = $c['next_date'] && $c['next_date'] < current_time( 'Y-m-d' ) && 'won' !== $k && 'lost' !== $k;
	return $c;
}
function fsp_board_data() {
	$out = array();
	foreach ( get_posts( array( 'post_type' => 'fsp_deal', 'post_status' => 'private', 'numberposts' => 3000, 'orderby' => 'modified', 'order' => 'DESC' ) ) as $d ) { $out[] = fsp_card( $d ); }
	return $out;
}
/** Same filter semantics as assets/pipeline.js (used by CSV export). */
function fsp_filter_cards( $cards, $f ) {
	$q = function_exists( 'mb_strtolower' ) ? mb_strtolower( (string) ( $f['q'] ?? '' ) ) : strtolower( (string) ( $f['q'] ?? '' ) );
	return array_values( array_filter( $cards, function ( $c ) use ( $f, $q ) {
		if ( ! empty( $f['stage'] ) && $c['stage'] !== $f['stage'] ) { return false; }
		if ( ! empty( $f['country'] ) && $c['country'] !== $f['country'] ) { return false; }
		if ( ! empty( $f['type'] ) && $c['project_type'] !== $f['type'] ) { return false; }
		if ( ! empty( $f['source'] ) && $c['source'] !== $f['source'] ) { return false; }
		if ( ! empty( $f['overdue'] ) && ! $c['overdue'] ) { return false; }
		if ( '' !== $q ) {
			$hay = implode( ' ', array( $c['title'], $c['contact_name'], $c['company_name'], $c['project_name'], $c['email'], $c['location'], $c['next_step'], $c['notes'] ) );
			$hay = function_exists( 'mb_strtolower' ) ? mb_strtolower( $hay ) : strtolower( $hay );
			if ( false === strpos( $hay, $q ) ) { return false; }
		}
		return true;
	} ) );
}

/* ---------- Menu ---------- */
add_action( 'admin_menu', function () {
	$board = add_submenu_page( 'fsq-run', 'Pipeline vânzări', 'Pipeline', FSQ_CAP, 'fsp-board', 'fsp_render_board' );
	$hooks = array( $board );
	foreach ( array( 'fsp_deal', 'fsp_company', 'fsp_contact' ) as $pt ) {
		$slug    = fsp_slug( $pt );
		$hooks[] = add_submenu_page( 'fsq-run', fsp_labels()[ $pt ][0], fsp_labels()[ $pt ][0], FSQ_CAP, $slug, function () use ( $pt, $slug ) { fsp_render_entity( $pt, $slug ); } );
	}
	$hooks[] = add_submenu_page( 'fsq-run', 'Import / export CSV', 'Import / export CSV', FSQ_CAP, 'fsp-import', 'fsp_render_import' );
	$hooks[] = add_submenu_page( 'fsq-run', 'Setări pipeline', 'Setări pipeline', FSQ_CAP, 'fsp-settings', 'fsp_render_settings' );
	foreach ( $hooks as $h ) { add_action( 'load-' . $h, 'fsq_noindex' ); }
	add_action( 'admin_enqueue_scripts', function ( $cur ) use ( $board, $hooks ) {
		if ( ! in_array( $cur, $hooks, true ) ) { return; }
		wp_enqueue_style( 'fsq', FSQ_URL . 'assets/fsq.css', array(), FSQ_VERSION );
		if ( $cur === $board ) {
			wp_enqueue_script( 'fsp-board', FSQ_URL . 'assets/pipeline.js', array(), FSQ_VERSION, true );
			$kinds = array(); foreach ( fsp_stage_list( true ) as $x ) { $kinds[ $x['key'] ] = $x['kind']; }
			wp_localize_script( 'fsp-board', 'FSP', array(
				'ajax' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'fsp_board' ),
				'edit' => admin_url( 'admin.php?page=fsp-deals&edit=' ), 'quest' => admin_url( 'admin.php?page=fsq-run&deal=' ),
				'export' => wp_nonce_url( admin_url( 'admin-post.php?action=fsp_export' ), 'fsp_export' ),
				'stages' => fsp_stages(), 'kinds' => $kinds, 'types' => fsp_project_types(), 'reasons' => fsp_lost_reasons(),
				'today' => current_time( 'Y-m-d' ), 'deals' => fsp_board_data(),
			) );
		}
	} );
}, 20 );

/* ---------- AJAX (logged-in admins only; no nopriv hooks) ---------- */
function fsp_ajax_guard() {
	if ( ! current_user_can( FSQ_CAP ) ) { wp_send_json_error( 'forbidden', 403 ); }
	check_ajax_referer( 'fsp_board', 'nonce' );
	$id = absint( $_POST['id'] ?? 0 );
	if ( get_post_type( $id ) !== 'fsp_deal' ) { wp_send_json_error( 'bad id', 400 ); }
	return $id;
}
add_action( 'wp_ajax_fsp_move', function () {
	$id    = fsp_ajax_guard();
	$stage = sanitize_key( $_POST['stage'] ?? '' );
	if ( ! isset( fsp_stages()[ $stage ] ) ) { wp_send_json_error( 'bad stage', 400 ); }
	$kind = fsp_stage_kind( $stage );
	$note = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );
	if ( 'won' === $kind && isset( $_POST['value'] ) && '' !== $_POST['value'] ) {
		update_post_meta( $id, '_fsp_value_eur', (string) (float) str_replace( ',', '.', wp_unslash( $_POST['value'] ) ) );
		$note = trim( 'Valoare finală: ' . fsp_m( $id, 'value_eur' ) . ' EUR. ' . $note );
	}
	if ( 'lost' === $kind ) {
		$r = sanitize_key( $_POST['reason'] ?? '' );
		$reasons = fsp_lost_reasons();
		if ( isset( $reasons[ $r ] ) ) { update_post_meta( $id, '_fsp_lost_reason', $r ); $note = trim( 'Motiv: ' . $reasons[ $r ] . '. ' . $note ); }
	}
	fsp_set_stage( $id, $stage, $note );
	wp_send_json_success( fsp_card( get_post( $id ) ) );
} );
add_action( 'wp_ajax_fsp_note', function () {
	$id   = fsp_ajax_guard();
	$text = sanitize_textarea_field( wp_unslash( $_POST['text'] ?? '' ) );
	if ( '' === $text ) { wp_send_json_error( 'empty', 400 ); }
	fsp_log( $id, array( 'type' => 'note', 'text' => $text ) );
	wp_update_post( array( 'ID' => $id ) ); // bump modified
	wp_send_json_success( fsp_card( get_post( $id ) ) );
} );
add_action( 'wp_ajax_fsp_timeline', function () {
	$id  = fsp_ajax_guard();
	$log = json_decode( (string) fsp_m( $id, 'log' ), true );
	wp_send_json_success( array_reverse( is_array( $log ) ? array_slice( $log, -50 ) : array() ) );
} );

/* ---------- Board screen ---------- */
function fsp_render_board() {
	if ( ! current_user_can( FSQ_CAP ) ) { return; }
	echo '<div class="wrap fsp-wrap"><h1 class="wp-heading-inline">Pipeline vânzări</h1> ';
	echo '<a class="page-title-action" href="' . esc_url( admin_url( 'admin.php?page=fsp-deals&edit=new' ) ) . '">Card nou</a> ';
	echo '<a class="page-title-action" href="' . esc_url( admin_url( 'admin.php?page=fsp-import' ) ) . '">Import CSV</a> ';
	echo '<a class="page-title-action" id="fsp-export" href="#">Export CSV (filtrul curent)</a> ';
	echo '<a class="page-title-action" href="' . esc_url( admin_url( 'admin.php?page=fsp-settings' ) ) . '">Setări coloane</a>';
	$msgs = get_transient( 'fsp_msg_' . get_current_user_id() );
	if ( $msgs ) { delete_transient( 'fsp_msg_' . get_current_user_id() ); foreach ( (array) $msgs as $m ) { echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $m ) . '</p></div>'; } }
	echo '<div id="fsp-filters" class="fsp-filters"></div>';
	echo '<div id="fsp-board" class="fsp-board"><p>Se încarcă…</p></div><div id="fsp-modal-root"></div></div>';
}

require_once __DIR__ . '/pipeline-admin.php';
require_once __DIR__ . '/pipeline-csv.php';
