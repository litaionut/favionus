<?php
/** Favionus Sales pipeline – forms, entity screens, settings (admin-only). */
defined( 'ABSPATH' ) || exit;

function fsp_sanitize_field( $type, $v ) {
	switch ( $type ) {
		case 'textarea': return sanitize_textarea_field( (string) $v );
		case 'email':    return sanitize_email( (string) $v );
		case 'url':      return esc_url_raw( (string) $v );
		case 'number':   $v = str_replace( array( ' ', ',' ), array( '', '.' ), (string) $v ); return is_numeric( $v ) ? (string) (float) $v : '';
		case 'date':     return preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $v ) ? (string) $v : '';
		case 'company':  return absint( $v );
		case 'contacts': return array_values( array_filter( array_map( 'absint', (array) $v ) ) );
		case 'ptype':    $v = sanitize_key( (string) $v ); return isset( fsp_project_types()[ $v ] ) ? $v : '';
		case 'stage':    $v = sanitize_key( (string) $v ); return fsp_stage_kind( $v ) ? $v : fsp_first_open();
		default:         return sanitize_text_field( (string) $v );
	}
}

/* ---------- Save / delete / note (admin-post) ---------- */
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
	$stage = null;
	foreach ( fsp_fields( $pt ) as $k => $def ) {
		$v = fsp_sanitize_field( $def[1], $in[ $k ] ?? '' );
		if ( 'stage' === $def[1] ) { $stage = $v; continue; }
		update_post_meta( $id, '_fsp_' . $k, $v );
	}
	if ( 'fsp_deal' === $pt ) {
		fsp_set_stage( $id, $stage ? $stage : fsp_first_open() );
		fsp_sync_entities( $id );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=' . fsp_slug( $pt ) . '&edit=' . $id . '&saved=1' ) );
	exit;
} );

add_action( 'admin_post_fsp_delete', function () {
	if ( ! current_user_can( FSQ_CAP ) ) { wp_die( 'Forbidden', 403 ); }
	$id = absint( $_GET['id'] ?? 0 );
	check_admin_referer( 'fsp_delete_' . $id );
	$pt = get_post_type( $id );
	if ( ! isset( fsp_labels()[ $pt ] ) ) { wp_die( 'Bad id', 400 ); }
	wp_delete_post( $id, true );
	wp_safe_redirect( admin_url( 'admin.php?page=' . fsp_slug( $pt ) . '&deleted=1' ) );
	exit;
} );

add_action( 'admin_post_fsp_add_note', function () {
	if ( ! current_user_can( FSQ_CAP ) ) { wp_die( 'Forbidden', 403 ); }
	$id = absint( $_POST['id'] ?? 0 );
	check_admin_referer( 'fsp_note_' . $id );
	if ( get_post_type( $id ) !== 'fsp_deal' ) { wp_die( 'Bad id', 400 ); }
	$text = sanitize_textarea_field( wp_unslash( $_POST['text'] ?? '' ) );
	if ( '' !== $text ) { fsp_log( $id, array( 'type' => 'note', 'text' => $text ) ); wp_update_post( array( 'ID' => $id ) ); }
	wp_safe_redirect( admin_url( 'admin.php?page=fsp-deals&edit=' . $id . '#fsp-timeline' ) );
	exit;
} );

/* ---------- Card from questionnaire record ---------- */
add_action( 'admin_post_fsp_from_record', function () {
	if ( ! current_user_can( FSQ_CAP ) ) { wp_die( 'Forbidden', 403 ); }
	$rid = absint( $_GET['record'] ?? 0 );
	check_admin_referer( 'fsp_from_record_' . $rid );
	if ( get_post_type( $rid ) !== FSQ_CPT ) { wp_die( 'Bad record', 400 ); }
	$ans = array();
	foreach ( (array) json_decode( (string) get_post_meta( $rid, '_fsq_answers', true ), true ) as $a ) {
		if ( ! empty( $a['field'] ) && isset( $a['answer'] ) && '—' !== $a['answer'] ) { $ans[ $a['field'] ] = $a['answer']; $ans[ $a['field'] . '__v' ] = $a['value'] ?? ''; }
	}
	$types = array( 'greenfield' => 'greenfield', 'mast_complement' => 'met_mast', 'repowering' => 'repowering', 'site_investigation' => 'site_investigation' );
	$deal = wp_insert_post( array( 'post_type' => 'fsp_deal', 'post_status' => 'private', 'post_title' => 'Card nou' ) );
	$units = isset( $ans['num_sites'] ) ? (string) (int) $ans['num_sites'] : '';
	$map = array(
		'contact_name' => get_post_meta( $rid, '_fsq_client', true ), 'company_name' => get_post_meta( $rid, '_fsq_company', true ),
		'location' => $ans['location'] ?? '', 'project_type' => $types[ $ans['project_type__v'] ?? '' ] ?? '',
		'site_type' => $ans['terrain'] ?? '', 'height' => $ans['max_height'] ?? ( $ans['hub_height'] ?? '' ),
		'duration' => $ans['duration'] ?? '', 'units' => '0' === $units ? '' : $units, 'next_step' => $ans['next_step'] ?? '',
		'source' => 'Chestionar', 'notes' => trim( ( isset( $ans['start'] ) ? 'Start dorit: ' . $ans['start'] . "\n" : '' ) . ( isset( $ans['budget'] ) ? 'Buget: ' . $ans['budget'] . "\n" : '' ) . get_post_meta( $rid, '_fsq_notes', true ) ),
	);
	foreach ( $map as $k => $v ) { update_post_meta( $deal, '_fsp_' . $k, sanitize_textarea_field( (string) $v ) ); }
	fsp_set_stage( $deal, fsp_stage_kind( 'meeting' ) ? 'meeting' : fsp_first_open(), 'Creat din fișa chestionarului' );
	fsp_link_record( $deal, $rid );
	fsp_sync_entities( $deal );
	wp_safe_redirect( admin_url( 'admin.php?page=fsp-deals&edit=' . $deal . '&fromrecord=1' ) );
	exit;
} );

/* Buttons on the questionnaire record view (called from the main plugin file). */
function fsp_record_actions( $rid ) {
	$deal = absint( get_post_meta( $rid, '_fsq_deal', true ) );
	echo '<p>';
	if ( $deal && get_post_type( $deal ) === 'fsp_deal' ) {
		echo '<a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=fsp-deals&edit=' . $deal ) ) . '">Card: ' . esc_html( fsq_title( $deal ) ) . '</a>';
	} else {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=fsp_from_record&record=' . $rid ), 'fsp_from_record_' . $rid );
		echo '<a class="button button-primary" href="' . esc_url( $url ) . '">Creează card din fișă</a>';
	}
	echo '</p>';
}

/* ---------- Generic list + edit screens ---------- */
function fsp_render_entity( $pt, $slug ) {
	if ( ! current_user_can( FSQ_CAP ) ) { return; }
	list( $plural, $single ) = fsp_labels()[ $pt ];
	$base = admin_url( 'admin.php?page=' . $slug );
	echo '<div class="wrap fsp-wrap">';
	$edit = isset( $_GET['edit'] ) ? sanitize_key( $_GET['edit'] ) : '';
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
	$cols = array( 'fsp_company' => array( 'vat', 'country', 'website' ), 'fsp_contact' => array( 'company', 'role', 'email', 'phone' ), 'fsp_deal' => array( 'company_name', 'project_name', 'stage', 'value_eur', 'next_date' ) )[ $pt ];
	$defs = fsp_fields( $pt );
	echo '<table class="widefat striped"><thead><tr><th>Nume</th>';
	foreach ( $cols as $c ) { echo '<th>' . esc_html( $defs[ $c ][0] ) . '</th>'; }
	echo '<th>Acțiuni</th></tr></thead><tbody>';
	foreach ( $items as $p ) {
		echo '<tr><td><a href="' . esc_url( $base . '&edit=' . $p->ID ) . '"><strong>' . esc_html( fsq_title( $p ) ) . '</strong></a></td>';
		foreach ( $cols as $c ) { echo '<td>' . esc_html( fsp_display( $p->ID, $c, $defs[ $c ][1] ) ) . '</td>'; }
		$del = wp_nonce_url( admin_url( 'admin-post.php?action=fsp_delete&id=' . $p->ID ), 'fsp_delete_' . $p->ID );
		echo '<td><a href="' . esc_url( $base . '&edit=' . $p->ID ) . '">Editează</a> | <a href="' . esc_url( $del ) . '" onclick="return confirm(\'Ștergi definitiv?\');">Șterge</a></td></tr>';
	}
	echo '</tbody></table></div>';
}

function fsp_display( $id, $k, $type ) {
	$v = fsp_m( $id, $k );
	if ( 'company' === $type ) { return fsp_title( absint( $v ) ); }
	if ( 'stage' === $type ) { foreach ( fsp_stage_list() as $x ) { if ( $x['key'] === $v ) { return $x['label']; } } return (string) $v; }
	if ( 'ptype' === $type ) { return fsp_project_types()[ $v ] ?? ''; }
	if ( 'value_eur' === $k && '' !== $v ) { return number_format_i18n( (float) $v ) . ' EUR'; }
	return is_array( $v ) ? '' : (string) $v;
}

function fsp_input( $pt, $id, $k, $def ) {
	$v    = $id ? fsp_m( $id, $k ) : ( 'stage' === $def[1] ? fsp_first_open() : '' );
	$name = 'f[' . $k . ']';
	switch ( $def[1] ) {
		case 'textarea':
			echo '<textarea name="' . esc_attr( $name ) . '" rows="5" class="large-text">' . esc_textarea( $v ) . '</textarea>'; break;
		case 'stage':
		case 'ptype':
			$opts = array();
			if ( 'stage' === $def[1] ) { foreach ( fsp_stage_list() as $x ) { $opts[ $x['key'] ] = $x['label'] . ( empty( $x['enabled'] ) ? ' (ascuns)' : '' ); } } else { $opts = fsp_project_types(); }
			echo '<select name="' . esc_attr( $name ) . '">';
			foreach ( $opts as $sk => $sl ) { echo '<option value="' . esc_attr( $sk ) . '"' . selected( $v, $sk, false ) . '>' . esc_html( $sl ) . '</option>'; }
			echo '</select>'; break;
		case 'company':
			echo '<select name="' . esc_attr( $name ) . '"><option value="0">—</option>';
			foreach ( fsp_all( 'fsp_company' ) as $o ) { echo '<option value="' . esc_attr( $o->ID ) . '"' . selected( absint( $v ), $o->ID, false ) . '>' . esc_html( fsq_title( $o ) ) . '</option>'; }
			echo '</select>'; break;
		case 'contacts':
			$sel = array_map( 'absint', (array) $v );
			echo '<select name="' . esc_attr( $name ) . '[]" multiple size="4" style="min-width:300px">';
			foreach ( fsp_all( 'fsp_contact' ) as $o ) {
				$co = fsp_title( absint( fsp_m( $o->ID, 'company' ) ) );
				echo '<option value="' . esc_attr( $o->ID ) . '"' . ( in_array( $o->ID, $sel, true ) ? ' selected' : '' ) . '>' . esc_html( fsq_title( $o ) . ( $co ? ' (' . $co . ')' : '' ) ) . '</option>';
			}
			echo '</select><p class="description">Ctrl/Cmd + click pentru mai multe.</p>'; break;
		case 'source':
			echo '<input type="text" list="fsp-sources" name="' . esc_attr( $name ) . '" class="regular-text" value="' . esc_attr( $v ) . '"><datalist id="fsp-sources">';
			global $wpdb;
			$src = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value <> '' LIMIT 50", '_fsp_source' ) );
			foreach ( array_unique( array_merge( array( 'LinkedIn', 'Website', 'Recomandare', 'Eveniment', 'Import CSV', 'Chestionar' ), $src ) ) as $s ) { echo '<option value="' . esc_attr( $s ) . '">'; }
			echo '</datalist>'; break;
		default:
			$t = array( 'email' => 'email', 'url' => 'url', 'number' => 'number', 'date' => 'date' )[ $def[1] ] ?? 'text';
			echo '<input type="' . esc_attr( $t ) . '"' . ( 'number' === $t ? ' step="any"' : '' ) . ' name="' . esc_attr( $name ) . '" class="regular-text" value="' . esc_attr( is_array( $v ) ? '' : $v ) . '">';
	}
}

function fsp_render_form( $pt, $id, $single, $base ) {
	$is_deal = 'fsp_deal' === $pt;
	echo '<h1>' . esc_html( $id ? $single . ': ' . fsq_title( $id ) : $single . ' nou' ) . '</h1>';
	echo '<p><a href="' . esc_url( $is_deal ? admin_url( 'admin.php?page=fsp-board' ) : $base ) . '">&larr; Înapoi</a>';
	if ( $is_deal && $id ) { echo ' &nbsp; <a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=fsq-run&deal=' . $id ) ) . '">Pornește chestionarul</a>'; }
	echo '</p>';
	if ( isset( $_GET['saved'] ) ) { echo '<div class="notice notice-success"><p>Salvat.</p></div>'; }
	if ( isset( $_GET['fromrecord'] ) ) { echo '<div class="notice notice-info"><p>Card creat din fișa chestionarului. Verifică și completează câmpurile, apoi salvează.</p></div>'; }
	echo '<div class="fsp-form-grid"><div>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'fsp_save_' . $pt . '_' . $id );
	echo '<input type="hidden" name="action" value="fsp_save"><input type="hidden" name="pt" value="' . esc_attr( $pt ) . '"><input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
	if ( $is_deal ) {
		echo '<input type="hidden" name="title" value="' . esc_attr( $id ? fsq_title( $id ) : '' ) . '">';
		echo '<p class="description">Titlul cardului = numele contactului (sau compania). Compania și contactul se creează / leagă automat.</p>';
	}
	$group = null;
	echo '<table class="form-table">';
	if ( ! $is_deal ) { echo '<tr><th><label for="fsp-title">Nume</label></th><td><input required id="fsp-title" name="title" class="regular-text" value="' . esc_attr( $id ? fsq_title( $id ) : '' ) . '"></td></tr>'; }
	foreach ( fsp_fields( $pt ) as $k => $def ) {
		if ( isset( $def[2] ) && $def[2] !== $group ) { $group = $def[2]; echo '<tr><th colspan="2"><h2 class="fsp-group">' . esc_html( $group ) . '</h2></th></tr>'; }
		echo '<tr><th><label>' . esc_html( $def[0] ) . '</label></th><td>';
		fsp_input( $pt, $id, $k, $def );
		echo '</td></tr>';
	}
	echo '</table>';
	submit_button( 'Salvează' );
	echo '</form></div>';

	if ( $is_deal && $id ) {
		echo '<div class="fsp-side">';
		$cid = absint( fsp_m( $id, 'company' ) ); $pid = absint( fsp_m( $id, 'contact' ) );
		echo '<h2>Legături</h2><ul>';
		if ( $cid ) { echo '<li>Companie: <a href="' . esc_url( admin_url( 'admin.php?page=fsp-companies&edit=' . $cid ) ) . '">' . esc_html( fsq_title( $cid ) ) . '</a></li>'; }
		if ( $pid ) { echo '<li>Contact: <a href="' . esc_url( admin_url( 'admin.php?page=fsp-contacts&edit=' . $pid ) ) . '">' . esc_html( fsq_title( $pid ) ) . '</a></li>'; }
		echo '</ul><h2>Fișe chestionar</h2>';
		$recs = fsp_record_ids( $id );
		if ( ! $recs ) { echo '<p>Nicio fișă. <a href="' . esc_url( admin_url( 'admin.php?page=fsq-run&deal=' . $id ) ) . '">Pornește chestionarul</a></p>'; }
		foreach ( $recs as $r ) {
			if ( get_post_type( $r ) !== FSQ_CPT ) { continue; }
			echo '<div class="fsp-rec"><a href="' . esc_url( admin_url( 'admin.php?page=fsq-records&view=' . $r ) ) . '"><strong>' . esc_html( fsq_title( $r ) ) . '</strong></a>';
			echo '<pre class="fsp-offer">' . esc_html( get_post_meta( $r, '_fsq_offer', true ) ) . '</pre></div>';
		}
		echo '<h2 id="fsp-timeline">Timeline</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'fsp_note_' . $id );
		echo '<input type="hidden" name="action" value="fsp_add_note"><input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
		echo '<textarea name="text" rows="3" class="large-text" placeholder="Notă nouă (apel, email, discuție)…" required></textarea><p><button class="button">Adaugă notă</button></p></form>';
		fsp_render_timeline( $id );
		echo '</div>';
	}
	echo '</div>';
	if ( 'fsp_company' === $pt && $id ) {
		echo '<h2>Contacte și carduri</h2><ul>';
		foreach ( array( 'fsp_contact', 'fsp_deal' ) as $rpt ) {
			foreach ( get_posts( array( 'post_type' => $rpt, 'post_status' => 'private', 'numberposts' => 200, 'meta_key' => '_fsp_company', 'meta_value' => $id ) ) as $r ) {
				echo '<li>' . esc_html( fsp_labels()[ $rpt ][1] ) . ': <a href="' . esc_url( admin_url( 'admin.php?page=' . fsp_slug( $rpt ) . '&edit=' . $r->ID ) ) . '">' . esc_html( fsq_title( $r ) ) . '</a></li>';
			}
		}
		echo '</ul>';
	}
}

function fsp_stage_label( $k ) { foreach ( fsp_stage_list() as $x ) { if ( $x['key'] === $k ) { return $x['label']; } } return $k ? $k : '—'; }
function fsp_render_timeline( $id ) {
	$log = json_decode( (string) fsp_m( $id, 'log' ), true );
	if ( ! $log ) { echo '<p>—</p>'; return; }
	$icons = array( 'stage' => '⇄', 'note' => '✎', 'record' => '📋', 'import' => '⤓', 'migrate' => '⚙' );
	echo '<ol class="fsp-timeline">';
	foreach ( array_reverse( $log ) as $e ) {
		$type = $e['type'] ?? 'stage';
		echo '<li class="fsp-tl-' . esc_attr( $type ) . '"><span class="fsp-tl-meta">' . esc_html( ( $icons[ $type ] ?? '•' ) . ' ' . ( $e['t'] ?? '' ) . ' · ' . ( $e['user'] ?? '' ) ) . '</span>';
		if ( in_array( $type, array( 'stage', 'migrate' ), true ) ) { echo '<div>' . esc_html( fsp_stage_label( $e['from'] ?? '' ) . ' → ' . fsp_stage_label( $e['to'] ?? '' ) ) . '</div>'; }
		if ( ! empty( $e['text'] ) ) { echo '<div>' . nl2br( esc_html( $e['text'] ) ) . '</div>'; }
		echo '</li>';
	}
	echo '</ol>';
}

/* ---------- Settings: columns ---------- */
function fsp_stage_counts() {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT pm.meta_value k, COUNT(*) n FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_type = %s GROUP BY pm.meta_value", '_fsp_stage', 'fsp_deal' ) );
	$o = array(); foreach ( (array) $rows as $r ) { $o[ $r->k ] = (int) $r->n; } return $o;
}
add_action( 'admin_post_fsp_save_stages', function () {
	if ( ! current_user_can( FSQ_CAP ) ) { wp_die( 'Forbidden', 403 ); }
	check_admin_referer( 'fsp_save_stages' );
	$old    = array(); foreach ( fsp_stage_list() as $x ) { $old[ $x['key'] ] = $x; }
	$counts = fsp_stage_counts();
	$in     = (array) wp_unslash( $_POST['s'] ?? array() );
	$msgs   = array();
	$new    = array();
	foreach ( $in as $key => $row ) {
		$key = sanitize_key( $key );
		if ( ! isset( $old[ $key ] ) ) { continue; }
		$x     = $old[ $key ];
		$label = sanitize_text_field( $row['label'] ?? '' );
		if ( '' !== $label ) { $x['label'] = $label; }
		$x['order'] = (int) ( $row['order'] ?? 0 );
		if ( ! empty( $row['delete'] ) ) {
			if ( 'open' === $x['kind'] && empty( $counts[ $key ] ) ) { $msgs[] = 'Coloana „' . $x['label'] . '” a fost ștearsă.'; unset( $old[ $key ] ); continue; }
			$msgs[] = 'Coloana „' . $x['label'] . '” nu poate fi ștearsă (are carduri sau e coloană de sistem).';
		}
		if ( 'lost' === $x['kind'] ) {
			$want = empty( $row['enabled'] ) ? 0 : 1;
			if ( ! $want && ! empty( $counts[ $key ] ) ) { $want = 1; $msgs[] = 'Coloana Pierdut are carduri, așa că rămâne vizibilă.'; }
			$x['enabled'] = $want;
		}
		$new[] = $x;
		unset( $old[ $key ] );
	}
	foreach ( $old as $key => $x ) { // rows not submitted: keep (never lose a column with cards)
		$x['order'] = 999; $new[] = $x;
	}
	$add = sanitize_text_field( wp_unslash( $_POST['new_label'] ?? '' ) );
	if ( '' !== $add ) {
		$base = substr( sanitize_key( remove_accents( $add ) ), 0, 30 ); $base = $base ? $base : 'col'; $key = $base; $i = 2;
		$keys = wp_list_pluck( $new, 'key' );
		while ( in_array( $key, $keys, true ) ) { $key = $base . '_' . $i++; }
		$new[] = array( 'key' => $key, 'label' => $add, 'kind' => 'open', 'enabled' => 1, 'order' => (int) ( $_POST['new_order'] ?? 50 ) );
		$msgs[] = 'Coloana „' . $add . '” a fost adăugată.';
	}
	usort( $new, function ( $a, $b ) { return ( $a['order'] ?? 0 ) <=> ( $b['order'] ?? 0 ); } );
	foreach ( $new as &$x ) { unset( $x['order'] ); } unset( $x );
	update_option( 'fsp_stages', $new, false );
	set_transient( 'fsp_smsg_' . get_current_user_id(), $msgs, 60 );
	wp_safe_redirect( admin_url( 'admin.php?page=fsp-settings&saved=1' ) );
	exit;
} );

function fsp_render_settings() {
	if ( ! current_user_can( FSQ_CAP ) ) { return; }
	$counts = fsp_stage_counts();
	$kinds  = array( 'backlog' => 'Backlog (lead-uri necontactate)', 'open' => 'În lucru', 'won' => 'Vândut (câștigat)', 'lost' => 'Pierdut' );
	echo '<div class="wrap fsp-wrap"><h1>Setări pipeline – coloane</h1>';
	$msgs = get_transient( 'fsp_smsg_' . get_current_user_id() );
	if ( $msgs ) { delete_transient( 'fsp_smsg_' . get_current_user_id() ); foreach ( $msgs as $m ) { echo '<div class="notice notice-info"><p>' . esc_html( $m ) . '</p></div>'; } }
	elseif ( isset( $_GET['saved'] ) ) { echo '<div class="notice notice-success"><p>Salvat.</p></div>'; }
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'fsp_save_stages' );
	echo '<input type="hidden" name="action" value="fsp_save_stages">';
	echo '<table class="widefat striped fsp-settings"><thead><tr><th>Ordine</th><th>Nume coloană</th><th>Tip</th><th>Carduri</th><th>Vizibilă</th><th>Șterge</th></tr></thead><tbody>';
	foreach ( fsp_stage_list() as $i => $x ) {
		$n   = 's[' . $x['key'] . ']';
		$cnt = $counts[ $x['key'] ] ?? 0;
		echo '<tr><td><input type="number" name="' . esc_attr( $n ) . '[order]" value="' . esc_attr( ( $i + 1 ) * 10 ) . '" style="width:70px"></td>';
		echo '<td><input type="text" name="' . esc_attr( $n ) . '[label]" value="' . esc_attr( $x['label'] ) . '" class="regular-text"> <code>' . esc_html( $x['key'] ) . '</code></td>';
		echo '<td>' . esc_html( $kinds[ $x['kind'] ] ?? $x['kind'] ) . '</td><td>' . esc_html( $cnt ) . '</td><td>';
		if ( 'lost' === $x['kind'] ) { echo '<label><input type="checkbox" name="' . esc_attr( $n ) . '[enabled]" value="1"' . checked( ! empty( $x['enabled'] ), true, false ) . '> Arată coloana Pierdut</label>'; }
		else { echo 'da'; }
		echo '</td><td>';
		if ( 'open' === $x['kind'] && ! $cnt ) { echo '<label><input type="checkbox" name="' . esc_attr( $n ) . '[delete]" value="1"> șterge</label>'; }
		else { echo '<span class="description">' . esc_html( 'open' === $x['kind'] ? 'are carduri' : 'coloană de sistem' ) . '</span>'; }
		echo '</td></tr>';
	}
	echo '<tr><td><input type="number" name="new_order" value="55" style="width:70px"></td><td colspan="5"><input type="text" name="new_label" class="regular-text" placeholder="Adaugă coloană nouă (ex. Ofertă trimisă)"></td></tr>';
	echo '</tbody></table><p class="description">Ordinea se stabilește după numărul din prima coloană (mic → stânga). Coloanele de sistem (Backlog, Vândut, Pierdut) pot fi redenumite și mutate, dar nu șterse. O coloană se poate șterge doar dacă e goală.</p>';
	submit_button( 'Salvează coloanele' );
	echo '</form></div>';
}
