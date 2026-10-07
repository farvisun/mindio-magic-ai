<?php
/**
 * Atomic, expiring integer counters in a plugin-owned table.
 *
 * Used for rate limits, budget reservations, and semaphores. Unlike
 * transients, increments are atomic under concurrent requests.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Counters {
	public static function get( string $bucket ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Counters must be read live.
		$value = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT value FROM %i WHERE bucket = %s AND expires_at >= UTC_TIMESTAMP()',
				Installer::table( 'counters' ),
				self::key( $bucket )
			)
		);
		return (int) $value;
	}

	/**
	 * Add to a counter and return the new value. An expired row restarts from zero.
	 */
	public static function add( string $bucket, int $amount, int $ttl ): int {
		global $wpdb;

		$table   = Installer::table( 'counters' );
		$key     = self::key( $bucket );
		$expires = gmdate( 'Y-m-d H:i:s', time() + max( 1, $ttl ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic upsert.
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (bucket, value, expires_at) VALUES (%s, %d, %s)
				ON DUPLICATE KEY UPDATE
					value = IF(expires_at < UTC_TIMESTAMP(), VALUES(value), value + VALUES(value)),
					expires_at = IF(expires_at < UTC_TIMESTAMP(), VALUES(expires_at), expires_at)',
				$table,
				$key,
				$amount,
				$expires
			)
		);

		return self::get( $bucket );
	}

	/**
	 * Add only when the result stays within the limit. Returns false when it would exceed it.
	 */
	public static function try_add( string $bucket, int $amount, int $limit, int $ttl ): bool {
		global $wpdb;

		$table   = Installer::table( 'counters' );
		$key     = self::key( $bucket );
		$expires = gmdate( 'Y-m-d H:i:s', time() + max( 1, $ttl ) );

		// Make sure a live row exists, resetting an expired one, without changing a live value.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic upsert.
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (bucket, value, expires_at) VALUES (%s, 0, %s)
				ON DUPLICATE KEY UPDATE
					value = IF(expires_at < UTC_TIMESTAMP(), 0, value),
					expires_at = IF(expires_at < UTC_TIMESTAMP(), VALUES(expires_at), expires_at)',
				$table,
				$key,
				$expires
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Conditional atomic increment.
		$updated = $wpdb->query(
			$wpdb->prepare(
				'UPDATE %i SET value = value + %d WHERE bucket = %s AND value + %d <= %d',
				$table,
				$amount,
				$key,
				$amount,
				$limit
			)
		);

		return 1 === (int) $updated;
	}

	public static function reset( string $bucket ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned counter row.
		$wpdb->delete( Installer::table( 'counters' ), array( 'bucket' => self::key( $bucket ) ), array( '%s' ) );
	}

	private static function key( string $bucket ): string {
		return strlen( $bucket ) > 180 ? substr( $bucket, 0, 116 ) . '#' . hash( 'sha256', $bucket ) : $bucket;
	}
}
