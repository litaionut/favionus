<?php
/** Favionus Sales pipeline – CSV import (upload → mapping → preview → import) and CSV export. Admin-only. */
defined( 'ABSPATH' ) || exit;

define( 'FSP_CSV_MAX_BYTES', 2 * 1024 * 1024 );
define( 'FSP_CSV_MAX_ROWS', 5000 );

/** Import targets (card fields) and header synonyms for auto-matching (normalized: lowercase, no diacritics, a-z0-9 + spaces). */
function fsp_import_targets() {
	return array(
		'contact_name' => array( 'Nume contact', array( 'name', 'nume', 'contact', 'contact name', 'nume contact', 'full name', 'persoana contact', 'person' ) ),
		'company_name' => array( 'Companie', array( 'company', 'companie', 'firma', 'company name', 'organization', 'organisation', 'organizatie', 'account' ) ),
		'role'         => array( 'Funcție', array( 'role', 'functie', 'job title', 'title', 'position', 'pozitie' ) ),
		'email'        => array( 'Email', array( 'email', 'e mail', 'mail', 'email address', 'adresa email' ) ),
		'phone'        => array( 'Telefon', array( 'phone', 'telefon', 'tel', 'mobile', 'mobil', 'phone number' ) ),
		'website'      => array( 'Website companie', array( 'website', 'site', 'web', 'url', 'company website' ) ),
		'project_name' => array( 'Nume proiect', array( 'project', 'proiect', 'project name', 'nume proiect' ) ),
		'country'      => array( 'Țară', array( 'country', 'tara' ) ),
		'location'     => array( 'Locație', array( 'location', 'locatie', 'judet', 'county', 'city', 'oras', 'region', 'regiune' ) ),
		'mw'           => array( 'Putere (MW)', array( 'mw', 'capacity', 'capacity mw', 'putere', 'putere mw' ) ),
		'project_type' => array( 'Tip proiect', array( 'project type', 'tip proiect' ) ),
		'notes'        => array( 'Descriere / note', array( 'notes', 'note', 'descriere', 'description', 'comments', 'comentarii' ) ),
		'source'       => array( 'Sursă lead', array( 'source', 'sursa', 'lead source', 'sursa lead' ) ),
		'value_eur'    => array( 'Valoare estimată (EUR)', array( 'value', 'valoare', 'value eur', 'valoare eur', 'estimated value', 'deal value' ) ),
		'next_step'    => array( 'Următorul pas', array( 'next step', 'urmatorul pas' ) ),
		'next_date'    => array( 'Dată follow-up', array( 'follow up', 'follow up date', 'next date', 'data follow up', 'followup' ) ),
	);
}
function fsp_norm( $s ) {
	$s = strtolower( remove_accents( (string) $s ) );
	return trim( preg_replace( '/[^a-z0-9]+/', ' ', $s ) );
}
function fsp_auto_map( $headers ) {
	$map = array(); $used = array();
	foreach ( fsp_import_targets() as $t => $def ) {
		foreach ( $headers as $i => $h ) {
			if ( isset( $used[ $i ] ) ) { continue; }
			if ( in_array( fsp_norm( $h ), $def[1], true ) ) { $map[ $t ] = $i; $used[ $i ] = 1; break; }
		}
	}
	return $map;
}
function fsp_norm_ptype( $v ) {
	$v = fsp_norm( $v );
	if ( '' === $v ) { return ''; }
	if ( false !== strpos( $v, 'repower' ) ) { return 'repowering'; }
	if ( false !== strpos( $v, 'mast' ) ) { return 'met_mast'; }
	if ( false !== strpos( $v, 'investig' ) ) { return 'site_investigation'; }
	if ( false !== strpos( $v, 'greenfield' ) ) { return 'greenfield'; }
	return null; // unknown value
}
function fsp_norm_date( $v ) {
	$v = trim( (string) $v );
	if ( '' === $v ) { return ''; }
	if ( preg_match( '/^(\d{4})-(\d{1,2})-(\d{1,2})/', $v, $m ) || preg_match( '/^(\d{1,2})[.\/](\d{1,2})[.\/](\d{4})$/', $v, $m2 ) ) {
		if ( isset( $m2 ) && $m2 ) { $m = array( 0, $m2[3], $m2[2], $m2[1] ); }
		if ( checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) { return sprintf( '%04d-%02d-%02d', $m[1], $m[2], $m[3] ); }
	}
	return null;
}
function fsp_norm_num( $v ) {
	$v = str_replace( array( ' ', "\xc2\xa0", 'EUR', 'eur', '€', 'MW', 'mw' ), '', (string) $v );
	if ( '' === $v ) { return ''; }
	if ( preg_match( '/^\d{1,3}(\.\d{3})+(,\d+)?$/', $v ) ) { $v = str_replace( array( '.', ',' ), array( '', '.' ), $v ); }
	$v = str_replace( ',', '.', $v );
	return is_numeric( $v ) ? (string) (float) $v : null;
}
function fsp_dedupe_key( $email, $company, $name ) {
	$email = strtolower( trim( (string) $email ) );
	if ( $email ) { return 'e:' . $email; }
	return 'n:' . fsp_norm( $company ) . '|' . fsp_norm( $name );
}

/**
 * Turn raw rows into card candidates. Returns array of [ 'status' => new|dup_existing|dup_file|invalid, 'warn' => [], 'f' => fields ].
 * $opts: dedupe (email_name|none), extras (bool: unmapped columns → notes), source, country.
 */
function fsp_import_process( $headers, $rows, $map, $opts ) {
	$existing = array();
	if ( 'none' !== ( $opts['dedupe'] ?? 'email_name' ) ) {
		foreach ( get_posts( array( 'post_type' => 'fsp_deal', 'post_status' => 'private', 'numberposts' => -1, 'fields' => 'ids' ) ) as $id ) {
			$existing[ fsp_dedupe_key( fsp_m( $id, 'email' ), fsp_m( $id, 'company_name' ), fsp_m( $id, 'contact_name' ) ) ] = 1;
			if ( fsp_m( $id, 'email' ) ) { $existing[ fsp_dedupe_key( '', fsp_m( $id, 'company_name' ), fsp_m( $id, 'contact_name' ) ) ] = 1; }
		}
	}
	$mapped = array_flip( array_map( 'intval', $map ) );
	$seen = array(); $out = array();
	foreach ( $rows as $r ) {
		$f = array(); $warn = array();
		foreach ( $map as $t => $i ) { $f[ $t ] = trim( (string) ( $r[ $i ] ?? '' ) ); }
		if ( ! empty( $opts['extras'] ) ) {
			$extra = array();
			foreach ( $headers as $i => $h ) {
				if ( isset( $mapped[ $i ] ) ) { continue; }
				$v = trim( (string) ( $r[ $i ] ?? '' ) );
				if ( '' !== $v ) { $extra[] = $h . ': ' . $v; }
			}
			if ( $extra ) { $f['notes'] = trim( ( $f['notes'] ?? '' ) . "\n" . implode( "\n", $extra ) ); }
		}
		if ( empty( $f['country'] ) && ! empty( $opts['country'] ) ) { $f['country'] = $opts['country']; }
		if ( empty( $f['source'] ) ) { $f['source'] = $opts['source'] ?? 'Import CSV'; }
		if ( ! empty( $f['email'] ) ) {
			$e = sanitize_email( $f['email'] );
			if ( ! is_email( $e ) ) { $warn[] = 'email invalid („' . $f['email'] . '”) – ignorat'; $e = ''; }
			$f['email'] = $e;
		}
		if ( ! empty( $f['website'] ) ) {
			if ( ! preg_match( '#^https?://#i', $f['website'] ) ) { $warn[] = 'website nu e URL („' . $f['website'] . '”) – mutat în note'; $f['notes'] = trim( ( $f['notes'] ?? '' ) . "\nwebsite: " . $f['website'] ); $f['website'] = ''; }
			else { $u = esc_url_raw( preg_replace( '/\s.*$/', '', $f['website'] ) ); if ( $u !== $f['website'] ) { $f['notes'] = trim( ( $f['notes'] ?? '' ) . "\nwebsite: " . $f['website'] ); } $f['website'] = $u; }
		}
		foreach ( array( 'mw', 'value_eur' ) as $k ) {
			if ( isset( $f[ $k ] ) && '' !== $f[ $k ] ) { $n = fsp_norm_num( $f[ $k ] ); if ( null === $n ) { $warn[] = $k . ' nu e număr („' . $f[ $k ] . '”)'; $n = ''; } $f[ $k ] = $n; }
		}
		if ( isset( $f['project_type'] ) && '' !== $f['project_type'] ) { $p = fsp_norm_ptype( $f['project_type'] ); if ( null === $p ) { $warn[] = 'tip proiect necunoscut („' . $f['project_type'] . '”)'; $p = ''; } $f['project_type'] = $p; }
		if ( isset( $f['next_date'] ) && '' !== $f['next_date'] ) { $d = fsp_norm_date( $f['next_date'] ); if ( null === $d ) { $warn[] = 'dată invalidă („' . $f['next_date'] . '”)'; $d = ''; } $f['next_date'] = $d; }
		foreach ( $f as $k => $v ) { $f[ $k ] = 'notes' === $k ? sanitize_textarea_field( $v ) : ( 'website' === $k ? $v : sanitize_text_field( $v ) ); }
		$status = 'new';
		if ( empty( $f['contact_name'] ) && empty( $f['company_name'] ) && empty( $f['email'] ) ) { $status = 'invalid'; $warn[] = 'lipsește numele, compania și emailul'; }
		elseif ( 'none' !== ( $opts['dedupe'] ?? 'email_name' ) ) {
			$keys = array( fsp_dedupe_key( $f['email'] ?? '', $f['company_name'] ?? '', $f['contact_name'] ?? '' ), fsp_dedupe_key( '', $f['company_name'] ?? '', $f['contact_name'] ?? '' ) );
			if ( array_intersect_key( $existing, array_flip( $keys ) ) ) { $status = 'dup_existing'; }
			elseif ( array_intersect_key( $seen, array_flip( $keys ) ) ) { $status = 'dup_file'; }
			foreach ( $keys as $k ) { $seen[ $k ] = 1; }
		}
		$out[] = array( 'status' => $status, 'warn' => $warn, 'f' => $f );
	}
	return $out;
}

/** Parse an uploaded CSV file path. Returns [headers, rows] or WP_Error. */
function fsp_parse_csv( $path ) {
	$fh = fopen( $path, 'r' );
	if ( ! $fh ) { return new WP_Error( 'open', 'Fișierul nu poate fi citit.' ); }
	$first = (string) fgets( $fh );
	$first = preg_replace( '/^\xEF\xBB\xBF/', '', $first );
	$delims = array( ',' => substr_count( $first, ',' ), ';' => substr_count( $first, ';' ), "\t" => substr_count( $first, "\t" ) );
	arsort( $delims ); $delim = key( $delims );
	rewind( $fh );
	$bom = fread( $fh, 3 ); if ( "\xEF\xBB\xBF" !== $bom ) { rewind( $fh ); }
	$headers = null; $rows = array();
	while ( false !== ( $r = fgetcsv( $fh, 0, $delim, '"', '' ) ) ) {
		if ( array( null ) === $r ) { continue; }
		$r = array_slice( $r, 0, 60 );
		foreach ( $r as &$c ) {
			$c = (string) $c;
			if ( function_exists( 'mb_check_encoding' ) && ! mb_check_encoding( $c, 'UTF-8' ) ) { $c = mb_convert_encoding( $c, 'UTF-8', 'Windows-1250' ); }
			$c = wp_check_invalid_utf8( substr( $c, 0, 4000 ), true );
		}
		unset( $c );
		if ( null === $headers ) { $headers = array_map( function ( $h ) { return sanitize_text_field( $h ); }, $r ); continue; }
		if ( '' === trim( implode( '', $r ) ) ) { continue; }
		$rows[] = $r;
		if ( count( $rows ) >= FSP_CSV_MAX_ROWS ) { break; }
	}
	fclose( $fh );
	if ( ! $headers || ! $rows ) { return new WP_Error( 'empty', 'CSV-ul nu are antet și rânduri.' ); }
	return array( $headers, $rows );
}

function fsp_imp_key() { return 'fsp_imp_' . get_current_user_id(); }

add_action( 'admin_post_fsp_import_upload', function () {
	if ( ! current_user_can( FSQ_CAP ) ) { wp_die( 'Forbidden', 403 ); }
	check_admin_referer( 'fsp_import_upload' );
	$back = admin_url( 'admin.php?page=fsp-import' );
	$f = $_FILES['csv'] ?? null;
	$err = '';
	if ( ! $f || ! is_array( $f ) || UPLOAD_ERR_OK !== (int) $f['error'] || ! is_uploaded_file( $f['tmp_name'] ) ) { $err = 'Încărcarea a eșuat.'; }
	elseif ( (int) $f['size'] <= 0 || (int) $f['size'] > FSP_CSV_MAX_BYTES ) { $err = 'Fișierul trebuie să aibă între 1 octet și 2 MB.'; }
	elseif ( ! preg_match( '/\.(csv|txt)$/i', (string) $f['name'] ) ) { $err = 'Doar fișiere .csv sau .txt.'; }
	else {
		$mime = function_exists( 'finfo_open' ) ? finfo_file( finfo_open( FILEINFO_MIME_TYPE ), $f['tmp_name'] ) : 'text/plain';
		if ( ! in_array( $mime, array( 'text/plain', 'text/csv', 'application/csv', 'text/x-csv', 'application/vnd.ms-excel', 'text/x-comma-separated-values' ), true ) ) { $err = 'Tip de fișier nepermis (' . $mime . ').'; }
	}
	if ( $err ) { set_transient( 'fsp_msg_' . get_current_user_id(), array( $err ), 120 ); wp_safe_redirect( $back ); exit; }
	$parsed = fsp_parse_csv( $f['tmp_name'] );
	@unlink( $f['tmp_name'] );
	if ( is_wp_error( $parsed ) ) { set_transient( 'fsp_msg_' . get_current_user_id(), array( $parsed->get_error_message() ), 120 ); wp_safe_redirect( $back ); exit; }
	$name = sanitize_file_name( $f['name'] );
	set_transient( fsp_imp_key(), array( 'name' => $name, 'headers' => $parsed[0], 'rows' => $parsed[1], 'map' => fsp_auto_map( $parsed[0] ),
		'opts' => array( 'dedupe' => 'email_name', 'extras' => 1, 'source' => 'Import CSV: ' . $name, 'country' => '', 'stage' => fsp_stage_by_kind( 'backlog' ) ) ), HOUR_IN_SECONDS );
	wp_safe_redirect( $back . '&step=map' );
	exit;
} );

function fsp_import_read_opts() {
	$t = fsp_import_targets(); $map = array();
	foreach ( (array) wp_unslash( $_POST['map'] ?? array() ) as $k => $i ) {
		$k = sanitize_key( $k );
		if ( isset( $t[ $k ] ) && '' !== $i && is_numeric( $i ) ) { $map[ $k ] = (int) $i; }
	}
	$stage = sanitize_key( $_POST['stage'] ?? '' );
	if ( ! in_array( fsp_stage_kind( $stage ), array( 'backlog', 'open' ), true ) ) { $stage = fsp_stage_by_kind( 'backlog' ); }
	return array( $map, array(
		'dedupe' => 'none' === ( $_POST['dedupe'] ?? '' ) ? 'none' : 'email_name', 'extras' => empty( $_POST['extras'] ) ? 0 : 1,
		'source' => sanitize_text_field( wp_unslash( $_POST['source'] ?? '' ) ), 'country' => sanitize_text_field( wp_unslash( $_POST['country'] ?? '' ) ), 'stage' => $stage,
	) );
}

add_action( 'admin_post_fsp_import_map', function () {
	if ( ! current_user_can( FSQ_CAP ) ) { wp_die( 'Forbidden', 403 ); }
	check_admin_referer( 'fsp_import_map' );
	$imp = get_transient( fsp_imp_key() );
	if ( ! $imp ) { wp_safe_redirect( admin_url( 'admin.php?page=fsp-import' ) ); exit; }
	list( $imp['map'], $imp['opts'] ) = fsp_import_read_opts();
	set_transient( fsp_imp_key(), $imp, HOUR_IN_SECONDS );
	wp_safe_redirect( admin_url( 'admin.php?page=fsp-import&step=' . ( isset( $_POST['run'] ) ? 'run' : 'preview' ) ) );
	exit;
} );

/** Create cards from processed rows. Returns counts. */
function fsp_import_commit( $processed, $stage, $file ) {
	$n = 0;
	foreach ( $processed as $p ) {
		if ( 'new' !== $p['status'] ) { continue; }
		$id = wp_insert_post( array( 'post_type' => 'fsp_deal', 'post_status' => 'private', 'post_title' => 'Import' ) );
		if ( ! $id || is_wp_error( $id ) ) { continue; }
		foreach ( $p['f'] as $k => $v ) { update_post_meta( $id, '_fsp_' . $k, $v ); }
		update_post_meta( $id, '_fsp_stage', $stage );
		fsp_log( $id, array( 'type' => 'import', 'to' => $stage, 'text' => 'Importat din ' . $file . ( $p['warn'] ? ' (' . implode( '; ', $p['warn'] ) . ')' : '' ) ) );
		fsp_sync_entities( $id );
		$n++;
	}
	return $n;
}

add_action( 'admin_post_fsp_import_run', function () {
	if ( ! current_user_can( FSQ_CAP ) ) { wp_die( 'Forbidden', 403 ); }
	check_admin_referer( 'fsp_import_run' );
	$imp = get_transient( fsp_imp_key() );
	if ( ! $imp ) { wp_safe_redirect( admin_url( 'admin.php?page=fsp-import' ) ); exit; }
	if ( function_exists( 'set_time_limit' ) ) { @set_time_limit( 300 ); }
	$proc = fsp_import_process( $imp['headers'], $imp['rows'], $imp['map'], $imp['opts'] );
	$n    = fsp_import_commit( $proc, $imp['opts']['stage'], $imp['name'] );
	delete_transient( fsp_imp_key() );
	set_transient( 'fsp_msg_' . get_current_user_id(), array( sprintf( 'Import gata: %d carduri create în „%s”; %d sărite (duplicate sau invalide).', $n, fsp_stage_label( $imp['opts']['stage'] ), count( $proc ) - $n ) ), 120 );
	wp_safe_redirect( admin_url( 'admin.php?page=fsp-board' ) );
	exit;
} );

add_action( 'admin_post_fsp_import_cancel', function () {
	if ( ! current_user_can( FSQ_CAP ) ) { wp_die( 'Forbidden', 403 ); }
	check_admin_referer( 'fsp_import_cancel' );
	delete_transient( fsp_imp_key() );
	wp_safe_redirect( admin_url( 'admin.php?page=fsp-import' ) );
	exit;
} );

/* ---------- Export ---------- */
function fsp_csv_cell( $v ) {
	$v = is_array( $v ) ? implode( ', ', $v ) : (string) $v;
	return preg_match( '/^[=+\-@\t\r]/', $v ) ? "'" . $v : $v; // neutralize spreadsheet formulas
}
add_action( 'admin_post_fsp_export', function () {
	if ( ! current_user_can( FSQ_CAP ) ) { wp_die( 'Forbidden', 403 ); }
	check_admin_referer( 'fsp_export' );
	$f = array();
	foreach ( array( 'stage', 'country', 'type', 'source', 'q' ) as $k ) { $f[ $k ] = sanitize_text_field( wp_unslash( $_GET[ $k ] ?? '' ) ); }
	$f['overdue'] = ! empty( $_GET['overdue'] );
	$cards = fsp_filter_cards( fsp_board_data(), $f );
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow', true );
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="favionus-pipeline-' . gmdate( 'Y-m-d' ) . '.csv"' );
	$out  = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" );
	$cols = array( 'id' => 'ID', 'stage' => 'Coloană', 'contact_name' => 'Nume contact', 'company_name' => 'Companie', 'role' => 'Funcție', 'email' => 'Email', 'phone' => 'Telefon',
		'website' => 'Website', 'project_name' => 'Proiect', 'country' => 'Țară', 'location' => 'Locație', 'mw' => 'MW', 'project_type' => 'Tip proiect', 'source' => 'Sursă',
		'value_eur' => 'Valoare EUR', 'next_step' => 'Următorul pas', 'next_date' => 'Dată follow-up', 'overdue' => 'Întârziat', 'notes' => 'Note', 'records' => 'Fișe chestionar', 'modified' => 'Modificat' );
	fputcsv( $out, array_values( $cols ), ',', '"', '' );
	$types = fsp_project_types();
	foreach ( $cards as $c ) {
		$row = array();
		foreach ( array_keys( $cols ) as $k ) {
			$v = $c[ $k ] ?? '';
			if ( 'stage' === $k ) { $v = fsp_stage_label( $v ); }
			if ( 'project_type' === $k ) { $v = $v ? ( $types[ $v ] ?? '' ) : ''; }
			if ( 'value_eur' === $k && ! $v ) { $v = ''; }
			if ( 'overdue' === $k ) { $v = $v ? 'da' : ''; }
			if ( 'records' === $k ) { $v = implode( '; ', wp_list_pluck( $c['records'], 'title' ) ); }
			$row[] = fsp_csv_cell( $v );
		}
		fputcsv( $out, $row, ',', '"', '' );
	}
	fclose( $out );
	exit;
} );

/* ---------- Import screen ---------- */
function fsp_render_import() {
	if ( ! current_user_can( FSQ_CAP ) ) { return; }
	$step = sanitize_key( $_GET['step'] ?? '' );
	$imp  = get_transient( fsp_imp_key() );
	$post = esc_url( admin_url( 'admin-post.php' ) );
	echo '<div class="wrap fsp-wrap"><h1>Import / export CSV</h1>';
	$msgs = get_transient( 'fsp_msg_' . get_current_user_id() );
	if ( $msgs ) { delete_transient( 'fsp_msg_' . get_current_user_id() ); foreach ( $msgs as $m ) { echo '<div class="notice notice-warning"><p>' . esc_html( $m ) . '</p></div>'; } }

	if ( ! $imp || ! in_array( $step, array( 'map', 'preview' ), true ) ) {
		echo '<h2>1. Încarcă CSV</h2><form method="post" enctype="multipart/form-data" action="' . $post . '">';
		wp_nonce_field( 'fsp_import_upload' );
		echo '<input type="hidden" name="action" value="fsp_import_upload"><input type="file" name="csv" accept=".csv,.txt,text/csv" required> ';
		submit_button( 'Încarcă și mapează coloanele', 'primary', 'submit', false );
		echo '<p class="description">Maxim 2 MB / ' . esc_html( FSP_CSV_MAX_ROWS ) . ' rânduri. Separator detectat automat (virgulă, punct și virgulă, tab). Prima linie = antet.</p></form>';
		echo '<h2>Export</h2><p>Exportul CSV se face din <a href="' . esc_url( admin_url( 'admin.php?page=fsp-board' ) ) . '">Pipeline</a> cu filtrele curente, sau tot: <a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=fsp_export' ), 'fsp_export' ) ) . '">Export toate cardurile</a></p></div>';
		return;
	}
	$headers = $imp['headers']; $map = $imp['map']; $o = $imp['opts'];
	echo '<p>Fișier: <strong>' . esc_html( $imp['name'] ) . '</strong> · ' . esc_html( count( $imp['rows'] ) ) . ' rânduri · ' . esc_html( count( $headers ) ) . ' coloane. ';
	echo '<form method="post" action="' . $post . '" style="display:inline">'; wp_nonce_field( 'fsp_import_cancel' );
	echo '<input type="hidden" name="action" value="fsp_import_cancel"><button class="button-link">Anulează importul</button></form></p>';

	echo '<form method="post" action="' . $post . '">'; wp_nonce_field( 'fsp_import_map' );
	echo '<input type="hidden" name="action" value="fsp_import_map"><h2>2. Mapare coloane</h2><table class="widefat striped fsp-map"><thead><tr><th>Câmp card</th><th>Coloană din CSV</th><th>Exemplu</th></tr></thead><tbody>';
	foreach ( fsp_import_targets() as $t => $def ) {
		$sel = $map[ $t ] ?? '';
		echo '<tr><td>' . esc_html( $def[0] ) . '</td><td><select name="map[' . esc_attr( $t ) . ']"><option value="">— nemapat —</option>';
		foreach ( $headers as $i => $h ) { echo '<option value="' . esc_attr( $i ) . '"' . selected( (string) $sel, (string) $i, false ) . '>' . esc_html( $h ) . '</option>'; }
		echo '</select>' . ( '' !== $sel ? ' <span class="fsp-auto">auto</span>' : '' ) . '</td><td class="fsp-ex">' . esc_html( '' !== $sel ? wp_html_excerpt( (string) ( $imp['rows'][0][ $sel ] ?? '' ), 60, '…' ) : '' ) . '</td></tr>';
	}
	echo '</tbody></table><h2>3. Opțiuni</h2><table class="form-table">';
	echo '<tr><th>Coloana țintă</th><td><select name="stage">';
	foreach ( fsp_stage_list( true ) as $x ) { if ( in_array( $x['kind'], array( 'backlog', 'open' ), true ) ) { echo '<option value="' . esc_attr( $x['key'] ) . '"' . selected( $o['stage'], $x['key'], false ) . '>' . esc_html( $x['label'] ) . '</option>'; } }
	echo '</select></td></tr>';
	echo '<tr><th>Duplicate</th><td><select name="dedupe"><option value="email_name"' . selected( $o['dedupe'], 'email_name', false ) . '>Sari peste duplicate (email, altfel companie + nume)</option><option value="none"' . selected( $o['dedupe'], 'none', false ) . '>Importă tot</option></select></td></tr>';
	echo '<tr><th>Coloane nemapate</th><td><label><input type="checkbox" name="extras" value="1"' . checked( ! empty( $o['extras'] ), true, false ) . '> Adaugă-le în „Descriere / note” (ex. „why_fit: …”)</label></td></tr>';
	echo '<tr><th>Sursă lead implicită</th><td><input name="source" class="regular-text" value="' . esc_attr( $o['source'] ) . '"></td></tr>';
	echo '<tr><th>Țară implicită</th><td><input name="country" class="regular-text" value="' . esc_attr( $o['country'] ) . '" placeholder="ex. România"></td></tr>';
	echo '</table><p><button class="button button-primary" name="preview" value="1">Previzualizare</button></p></form>';

	if ( 'preview' === $step ) {
		$proc = fsp_import_process( $headers, $imp['rows'], $map, $o );
		$c = array( 'new' => 0, 'clean' => 0, 'dup_existing' => 0, 'dup_file' => 0, 'invalid' => 0 );
		foreach ( $proc as $p ) { $c[ $p['status'] ]++; if ( 'new' === $p['status'] && ! $p['warn'] ) { $c['clean']++; } }
		echo '<h2>4. Previzualizare</h2><p><strong>' . esc_html( $c['new'] ) . '</strong> carduri noi (' . esc_html( $c['clean'] ) . ' fără avertismente) · ' . esc_html( $c['dup_existing'] ) . ' există deja · ' . esc_html( $c['dup_file'] ) . ' duplicate în fișier · ' . esc_html( $c['invalid'] ) . ' invalide</p>';
		echo '<table class="widefat striped fsp-preview"><thead><tr><th>#</th><th>Status</th><th>Contact</th><th>Companie</th><th>Email</th><th>Țară / locație</th><th>Sursă</th><th>Avertismente</th></tr></thead><tbody>';
		$labels = array( 'new' => 'nou', 'dup_existing' => 'există', 'dup_file' => 'duplicat', 'invalid' => 'invalid' );
		foreach ( array_slice( $proc, 0, 200 ) as $i => $p ) {
			$f = $p['f'];
			echo '<tr class="fsp-st-' . esc_attr( $p['status'] ) . '"><td>' . esc_html( $i + 2 ) . '</td><td>' . esc_html( $labels[ $p['status'] ] ) . '</td><td>' . esc_html( $f['contact_name'] ?? '' ) . '</td><td>' . esc_html( $f['company_name'] ?? '' ) . '</td><td>' . esc_html( $f['email'] ?? '' ) . '</td><td>' . esc_html( trim( ( $f['country'] ?? '' ) . ' ' . ( $f['location'] ?? '' ) ) ) . '</td><td>' . esc_html( $f['source'] ?? '' ) . '</td><td>' . esc_html( implode( '; ', $p['warn'] ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
		if ( count( $proc ) > 200 ) { echo '<p class="description">Primele 200 de rânduri afișate.</p>'; }
		echo '<form method="post" action="' . $post . '">'; wp_nonce_field( 'fsp_import_run' );
		echo '<input type="hidden" name="action" value="fsp_import_run"><p><button class="button button-primary button-hero"' . ( $c['new'] ? '' : ' disabled' ) . '>Importă ' . esc_html( $c['new'] ) . ' carduri în „' . esc_html( fsp_stage_label( $o['stage'] ) ) . '”</button></p></form>';
	}
	echo '</div>';
}
