<?php
/**
 * Real LiDAR fleet availability: DB tables, wp-admin screen (Tools › LiDAR fleet),
 * REST endpoint /wp-json/favionus/v1/availability.
 *
 * @package doppler_lidar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/fleet-core.php';

const DOPPLER_LIDAR_FLEET_DB_VERSION = '1';

/**
 * Table names.
 *
 * @return array{units:string,bookings:string}
 */
function doppler_lidar_fleet_tables() {
	global $wpdb;
	return array(
		'units'    => $wpdb->prefix . 'favionus_units',
		'bookings' => $wpdb->prefix . 'favionus_bookings',
	);
}

/**
 * Create or upgrade the tables (dbDelta). Runs on theme activation and once on init.
 */
function doppler_lidar_fleet_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$t       = doppler_lidar_fleet_tables();
	$charset = $wpdb->get_charset_collate();

	dbDelta(
		"CREATE TABLE {$t['units']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			model varchar(191) NOT NULL DEFAULT '',
			max_range_m int(10) unsigned DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY status (status)
		) $charset;"
	);
	dbDelta(
		"CREATE TABLE {$t['bookings']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			unit_id bigint(20) unsigned NOT NULL,
			start_date date NOT NULL,
			end_date date NOT NULL,
			note varchar(255) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY unit_period (unit_id,start_date,end_date)
		) $charset;"
	);
	update_option( 'doppler_lidar_fleet_db_version', DOPPLER_LIDAR_FLEET_DB_VERSION, false );
}
add_action( 'after_switch_theme', 'doppler_lidar_fleet_install' );
add_action(
	'init',
	static function () {
		if ( get_option( 'doppler_lidar_fleet_db_version' ) !== DOPPLER_LIDAR_FLEET_DB_VERSION ) {
			doppler_lidar_fleet_install();
		}
	}
);

/**
 * Number of active units (0 = fleet not configured → plain request form).
 */
function doppler_lidar_fleet_active_count() {
	global $wpdb;
	$t = doppler_lidar_fleet_tables();
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from prefix.
	$n = $wpdb->get_var( "SELECT COUNT(*) FROM {$t['units']} WHERE status = 'active'" );
	return (int) $n;
}

/**
 * Free units for a period, from the DB.
 *
 * @param string $start Y-m-d.
 * @param string $end   Y-m-d.
 * @return int
 */
function doppler_lidar_fleet_free_units( $start, $end ) {
	global $wpdb;
	$t = doppler_lidar_fleet_tables();
	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$units    = $wpdb->get_results( "SELECT id, status FROM {$t['units']}", ARRAY_A );
	$bookings = $wpdb->get_results(
		$wpdb->prepare( "SELECT unit_id, start_date, end_date FROM {$t['bookings']} WHERE start_date <= %s AND end_date >= %s", $end, $start ),
		ARRAY_A
	);
	// phpcs:enable
	return doppler_lidar_fleet_count_free( (array) $units, (array) $bookings, $start, $end );
}

/**
 * Validate a period. Returns WP_Error or array( start, end ).
 *
 * @param mixed $start Start.
 * @param mixed $end   End.
 */
function doppler_lidar_fleet_period( $start, $end ) {
	$start = sanitize_text_field( (string) $start );
	$end   = sanitize_text_field( (string) $end );
	if ( ! doppler_lidar_fleet_valid_date( $start ) || ! doppler_lidar_fleet_valid_date( $end ) || $end < $start ) {
		return new WP_Error( 'favionus_bad_dates', __( 'Please choose a valid start and end date.', 'doppler_lidar' ), array( 'status' => 400 ) );
	}
	if ( ( strtotime( $end ) - strtotime( $start ) ) > 3 * 366 * DAY_IN_SECONDS ) {
		return new WP_Error( 'favionus_bad_dates', __( 'Please choose a period of up to three years.', 'doppler_lidar' ), array( 'status' => 400 ) );
	}
	return array( $start, $end );
}

/**
 * Simple per-IP rate limit (20 checks per 10 minutes).
 */
function doppler_lidar_fleet_rate_limited() {
	$ip  = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : ( isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$key = 'fav_av_' . md5( (string) $ip . wp_salt( 'nonce' ) );
	$n   = (int) get_transient( $key );
	if ( $n >= 20 ) {
		return true;
	}
	set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );
	return false;
}

/**
 * REST: GET /wp-json/favionus/v1/availability?start=Y-m-d&end=Y-m-d&units=N&nonce=...
 */
add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'favionus/v1',
			'/availability',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'args'                => array(
					'start' => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
					'end'   => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
					'units' => array( 'required' => true, 'type' => 'integer', 'minimum' => 1, 'maximum' => 99, 'sanitize_callback' => 'absint' ),
					'nonce' => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
				),
				'callback'            => 'doppler_lidar_fleet_rest_availability',
			)
		);
	}
);

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function doppler_lidar_fleet_rest_availability( $request ) {
	if ( ! wp_verify_nonce( $request['nonce'], 'favionus_availability' ) ) {
		return new WP_Error( 'favionus_nonce', __( 'Please refresh the page and try again.', 'doppler_lidar' ), array( 'status' => 403 ) );
	}
	if ( doppler_lidar_fleet_rate_limited() ) {
		return new WP_Error( 'favionus_rate', __( 'Too many checks. Please wait a few minutes or send us a request.', 'doppler_lidar' ), array( 'status' => 429 ) );
	}
	$period = doppler_lidar_fleet_period( $request['start'], $request['end'] );
	if ( is_wp_error( $period ) ) {
		return $period;
	}
	$requested = max( 1, (int) $request['units'] );
	if ( 0 === doppler_lidar_fleet_active_count() ) {
		$response = array( 'configured' => false );
	} else {
		$free     = doppler_lidar_fleet_free_units( $period[0], $period[1] );
		$response = array(
			'configured' => true,
			'available'  => $free,
			'requested'  => $requested,
			'enough'     => $free >= $requested,
		);
	}
	$res = rest_ensure_response( $response );
	$res->header( 'Cache-Control', 'no-store' );
	return $res;
}

/* ---------------------------------------------------------------------------
 * wp-admin: Tools › LiDAR fleet
 * ------------------------------------------------------------------------ */

add_action(
	'admin_menu',
	static function () {
		add_management_page( __( 'LiDAR fleet', 'doppler_lidar' ), __( 'LiDAR fleet', 'doppler_lidar' ), 'manage_options', 'favionus-fleet', 'doppler_lidar_fleet_admin_page' );
	}
);

/**
 * Handle admin form posts.
 */
add_action(
	'admin_post_favionus_fleet',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'doppler_lidar' ), 403 );
		}
		check_admin_referer( 'favionus_fleet' );
		global $wpdb;
		$t   = doppler_lidar_fleet_tables();
		$op  = isset( $_POST['op'] ) ? sanitize_key( wp_unslash( $_POST['op'] ) ) : '';
		$msg = 'saved';
		$now = current_time( 'mysql' );
		$statuses = array( 'active', 'maintenance', 'retired' );

		if ( 'add_unit' === $op || 'update_unit' === $op ) {
			$name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$model  = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : '';
			$range  = isset( $_POST['max_range_m'] ) && '' !== $_POST['max_range_m'] ? absint( $_POST['max_range_m'] ) : null;
			$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active';
			if ( ! in_array( $status, $statuses, true ) ) {
				$status = 'active';
			}
			if ( '' === $name ) {
				$msg = 'error';
			} elseif ( 'add_unit' === $op ) {
				$wpdb->insert( $t['units'], array( 'name' => $name, 'model' => $model, 'max_range_m' => $range, 'status' => $status, 'created_at' => $now ) );
			} else {
				$wpdb->update( $t['units'], array( 'name' => $name, 'model' => $model, 'max_range_m' => $range, 'status' => $status ), array( 'id' => absint( $_POST['id'] ?? 0 ) ) );
			}
		} elseif ( 'delete_unit' === $op ) {
			$id = absint( $_POST['id'] ?? 0 );
			$wpdb->delete( $t['bookings'], array( 'unit_id' => $id ) );
			$wpdb->delete( $t['units'], array( 'id' => $id ) );
		} elseif ( 'add_booking' === $op ) {
			$unit   = absint( $_POST['unit_id'] ?? 0 );
			$period = doppler_lidar_fleet_period( wp_unslash( $_POST['start_date'] ?? '' ), wp_unslash( $_POST['end_date'] ?? '' ) );
			$note   = isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '';
			if ( ! $unit || is_wp_error( $period ) ) {
				$msg = 'error';
			} else {
				$wpdb->insert( $t['bookings'], array( 'unit_id' => $unit, 'start_date' => $period[0], 'end_date' => $period[1], 'note' => $note, 'created_at' => $now ) );
			}
		} elseif ( 'delete_booking' === $op ) {
			$wpdb->delete( $t['bookings'], array( 'id' => absint( $_POST['id'] ?? 0 ) ) );
		}
		wp_safe_redirect( admin_url( 'tools.php?page=favionus-fleet&msg=' . $msg ) );
		exit;
	}
);

/**
 * Small helper: hidden fields for an admin action form.
 *
 * @param string $op Operation.
 */
function doppler_lidar_fleet_form_open( $op, $id = '' ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline"' . ( $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . '>';
	echo '<input type="hidden" name="action" value="favionus_fleet"><input type="hidden" name="op" value="' . esc_attr( $op ) . '">';
	wp_nonce_field( 'favionus_fleet' );
}

/**
 * Render Tools › LiDAR fleet.
 */
function doppler_lidar_fleet_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	global $wpdb;
	$t = doppler_lidar_fleet_tables();
	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$units    = $wpdb->get_results( "SELECT * FROM {$t['units']} ORDER BY name", ARRAY_A );
	$bookings = $wpdb->get_results( "SELECT b.*, u.name AS unit_name FROM {$t['bookings']} b LEFT JOIN {$t['units']} u ON u.id = b.unit_id WHERE b.end_date >= CURDATE() - INTERVAL 30 DAY ORDER BY b.start_date", ARRAY_A );
	// phpcs:enable
	$statuses = array( 'active' => __( 'Active', 'doppler_lidar' ), 'maintenance' => __( 'Maintenance', 'doppler_lidar' ), 'retired' => __( 'Retired', 'doppler_lidar' ) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'LiDAR fleet', 'doppler_lidar' ); ?></h1>
		<?php if ( isset( $_GET['msg'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice <?php echo 'error' === $_GET['msg'] ? 'notice-error' : 'notice-success'; // phpcs:ignore ?>"><p><?php echo 'error' === $_GET['msg'] ? esc_html__( 'Please check the fields (name required; valid dates, end after start).', 'doppler_lidar' ) : esc_html__( 'Saved.', 'doppler_lidar' ); // phpcs:ignore ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Only units with status "Active" are counted by the website availability check. A unit is free for a period if none of its bookings overlap it. With no active units, the website shows a plain request form instead of a check.', 'doppler_lidar' ); ?></p>

		<h2><?php esc_html_e( 'Units', 'doppler_lidar' ); ?></h2>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Name', 'doppler_lidar' ); ?></th><th><?php esc_html_e( 'Model', 'doppler_lidar' ); ?></th><th><?php esc_html_e( 'Max range (m)', 'doppler_lidar' ); ?></th><th><?php esc_html_e( 'Status', 'doppler_lidar' ); ?></th><th></th></tr></thead><tbody>
		<?php foreach ( $units as $u ) : $fid = 'fav-unit-' . (int) $u['id']; ?>
			<tr>
				<td><input form="<?php echo esc_attr( $fid ); ?>" name="name" value="<?php echo esc_attr( $u['name'] ); ?>" required></td>
				<td><input form="<?php echo esc_attr( $fid ); ?>" name="model" value="<?php echo esc_attr( $u['model'] ); ?>"></td>
				<td><input form="<?php echo esc_attr( $fid ); ?>" name="max_range_m" type="number" min="0" value="<?php echo esc_attr( (string) $u['max_range_m'] ); ?>" style="width:7em"></td>
				<td><select form="<?php echo esc_attr( $fid ); ?>" name="status"><?php foreach ( $statuses as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $u['status'], $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select></td>
				<td>
					<?php doppler_lidar_fleet_form_open( 'update_unit', $fid ); ?><input type="hidden" name="id" value="<?php echo (int) $u['id']; ?>"><button class="button"><?php esc_html_e( 'Save', 'doppler_lidar' ); ?></button></form>
					<?php doppler_lidar_fleet_form_open( 'delete_unit' ); ?><input type="hidden" name="id" value="<?php echo (int) $u['id']; ?>"><button class="button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Delete this unit and its bookings?', 'doppler_lidar' ) ); ?>')"><?php esc_html_e( 'Delete', 'doppler_lidar' ); ?></button></form>
				</td>
			</tr>
		<?php endforeach; ?>
		<?php if ( ! $units ) : ?><tr><td colspan="5"><?php esc_html_e( 'No units yet.', 'doppler_lidar' ); ?></td></tr><?php endif; ?>
		</tbody></table>

		<h3><?php esc_html_e( 'Add unit', 'doppler_lidar' ); ?></h3>
		<?php doppler_lidar_fleet_form_open( 'add_unit' ); ?>
			<input name="name" placeholder="<?php esc_attr_e( 'Name (e.g. Unit 1)', 'doppler_lidar' ); ?>" required>
			<input name="model" placeholder="<?php esc_attr_e( 'Manufacturer / model', 'doppler_lidar' ); ?>">
			<input name="max_range_m" type="number" min="0" placeholder="<?php esc_attr_e( 'Max range (m)', 'doppler_lidar' ); ?>" style="width:9em">
			<select name="status"><?php foreach ( $statuses as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select>
			<button class="button button-primary"><?php esc_html_e( 'Add unit', 'doppler_lidar' ); ?></button>
		</form>

		<h2 style="margin-top:2em"><?php esc_html_e( 'Bookings (current and future)', 'doppler_lidar' ); ?></h2>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Unit', 'doppler_lidar' ); ?></th><th><?php esc_html_e( 'From', 'doppler_lidar' ); ?></th><th><?php esc_html_e( 'To', 'doppler_lidar' ); ?></th><th><?php esc_html_e( 'Note', 'doppler_lidar' ); ?></th><th></th></tr></thead><tbody>
		<?php foreach ( $bookings as $b ) : ?>
			<tr><td><?php echo esc_html( (string) $b['unit_name'] ); ?></td><td><?php echo esc_html( $b['start_date'] ); ?></td><td><?php echo esc_html( $b['end_date'] ); ?></td><td><?php echo esc_html( $b['note'] ); ?></td>
			<td><?php doppler_lidar_fleet_form_open( 'delete_booking' ); ?><input type="hidden" name="id" value="<?php echo (int) $b['id']; ?>"><button class="button-link-delete"><?php esc_html_e( 'Delete', 'doppler_lidar' ); ?></button></form></td></tr>
		<?php endforeach; ?>
		<?php if ( ! $bookings ) : ?><tr><td colspan="5"><?php esc_html_e( 'No bookings.', 'doppler_lidar' ); ?></td></tr><?php endif; ?>
		</tbody></table>

		<h3><?php esc_html_e( 'Add booking', 'doppler_lidar' ); ?></h3>
		<?php if ( $units ) : ?>
		<?php doppler_lidar_fleet_form_open( 'add_booking' ); ?>
			<select name="unit_id" required><?php foreach ( $units as $u ) : ?><option value="<?php echo (int) $u['id']; ?>"><?php echo esc_html( $u['name'] ); ?></option><?php endforeach; ?></select>
			<label><?php esc_html_e( 'From', 'doppler_lidar' ); ?> <input type="date" name="start_date" required></label>
			<label><?php esc_html_e( 'To', 'doppler_lidar' ); ?> <input type="date" name="end_date" required></label>
			<input name="note" placeholder="<?php esc_attr_e( 'Note (client, project)', 'doppler_lidar' ); ?>">
			<button class="button button-primary"><?php esc_html_e( 'Add booking', 'doppler_lidar' ); ?></button>
		</form>
		<?php else : ?><p><?php esc_html_e( 'Add a unit first.', 'doppler_lidar' ); ?></p><?php endif; ?>
	</div>
	<?php
}
