<?php
/**
 * Ordered event log of a run. Streams and polling clients read the same rows.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Agent;

use MindioMagicAI\AI\Json;
use MindioMagicAI\Core\Installer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RunEvents {
	/**
	 * @param array<string,mixed> $payload
	 */
	public static function append( int $run_id, string $type, array $payload ): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned event log.
		$wpdb->insert(
			Installer::table( 'run_events' ),
			array(
				'run_id'     => $run_id,
				'type'       => $type,
				'payload'    => Json::encode( $payload ),
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * @return array<int,array{id:int,type:string,payload:mixed}>
	 */
	public static function since( int $run_id, int $after, int $limit = 500 ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Event log reads must be live.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, type, payload FROM %i WHERE run_id = %d AND id > %d ORDER BY id ASC LIMIT %d',
				Installer::table( 'run_events' ),
				$run_id,
				$after,
				max( 1, min( 1000, $limit ) )
			),
			ARRAY_A
		);
		return array_map(
			static fn( array $row ): array => array(
				'id'      => (int) $row['id'],
				'type'    => (string) $row['type'],
				'payload' => json_decode( (string) $row['payload'], true ),
			),
			(array) $rows
		);
	}
}
