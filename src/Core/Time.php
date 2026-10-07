<?php
/**
 * Time formatting for REST responses.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Time {
	/**
	 * A GMT database time as ISO 8601 in UTC. mysql_to_rfc3339() drops the zone, which
	 * browsers then read as local time.
	 */
	public static function iso( ?string $gmt ): string {
		if ( null === $gmt || '' === $gmt || str_starts_with( $gmt, '0000-00-00' ) ) {
			return '';
		}
		$time = strtotime( $gmt . ' UTC' );
		return false === $time ? '' : gmdate( 'Y-m-d\TH:i:s\Z', $time );
	}
}
