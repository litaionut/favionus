<?php
/**
 * Pure availability logic (no WordPress dependencies, unit-testable).
 *
 * @package doppler_lidar
 */

/**
 * Validate a Y-m-d date string.
 *
 * @param string $date Date.
 * @return bool
 */
function doppler_lidar_fleet_valid_date( $date ) {
	if ( ! is_string( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		return false;
	}
	list( $y, $m, $d ) = array_map( 'intval', explode( '-', $date ) );
	return checkdate( $m, $d, $y );
}

/**
 * Count units free for the whole period [start, end] (inclusive dates, Y-m-d).
 *
 * A unit is free if its status is "active" and none of its bookings overlap the period.
 * Overlap: booking.start <= end AND booking.end >= start.
 *
 * @param array  $units    List of arrays with keys id, status.
 * @param array  $bookings List of arrays with keys unit_id, start_date, end_date.
 * @param string $start    Period start.
 * @param string $end      Period end.
 * @return int Number of free units.
 */
function doppler_lidar_fleet_count_free( array $units, array $bookings, $start, $end ) {
	$busy = array();
	foreach ( $bookings as $b ) {
		if ( (string) $b['start_date'] <= $end && (string) $b['end_date'] >= $start ) {
			$busy[ (int) $b['unit_id'] ] = true;
		}
	}
	$free = 0;
	foreach ( $units as $u ) {
		if ( 'active' === $u['status'] && empty( $busy[ (int) $u['id'] ] ) ) {
			++$free;
		}
	}
	return $free;
}
