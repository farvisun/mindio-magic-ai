<?php
/**
 * Token and cost accounting for every provider call.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UsageLedger {
	/**
	 * @param array{input:int,output:int,cache_read:int,cache_write:int} $usage
	 */
	public static function record( string $feature, int $connection_id, string $provider, string $model, int $run_id, array $usage, int $cost_micros, bool $estimated ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Append-only write to the plugin-owned ledger.
		$wpdb->insert(
			Installer::table( 'usage' ),
			array(
				'created_at'         => current_time( 'mysql', true ),
				'user_id'            => get_current_user_id(),
				'feature'            => sanitize_key( $feature ),
				'connection_id'      => $connection_id,
				'provider'           => sanitize_key( $provider ),
				'model'              => Settings::model_id( $model ),
				'run_id'             => $run_id,
				'input_tokens'       => max( 0, (int) $usage['input'] ),
				'output_tokens'      => max( 0, (int) $usage['output'] ),
				'cache_read_tokens'  => max( 0, (int) $usage['cache_read'] ),
				'cache_write_tokens' => max( 0, (int) $usage['cache_write'] ),
				'cost_micros'        => max( 0, $cost_micros ),
				'estimated'          => $estimated ? 1 : 0,
			),
			array( '%s', '%d', '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d' )
		);
	}

	/**
	 * Usage report for the trailing window, with daily buckets in site-local time.
	 *
	 * @return array<string,mixed>
	 */
	public static function report( int $days ): array {
		global $wpdb;

		$days   = max( 1, min( 365, $days ) );
		$offset = (int) round( (float) get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS );
		$today  = (int) floor( ( time() + $offset ) / DAY_IN_SECONDS ) * DAY_IN_SECONDS;
		$start  = $today - ( $days - 1 ) * DAY_IN_SECONDS;
		$since  = gmdate( 'Y-m-d H:i:s', $start - $offset );
		$table  = Installer::table( 'usage' );

		$daily = array();
		for ( $day = $start; $day <= $today; $day += DAY_IN_SECONDS ) {
			$daily[ gmdate( 'Y-m-d', $day ) ] = array(
				'date'          => gmdate( 'Y-m-d', $day ),
				'input_tokens'  => 0,
				'output_tokens' => 0,
				'cost_micros'   => 0,
				'calls'         => 0,
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Fixed-shape aggregate over the plugin-owned ledger.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT DATE(DATE_ADD(created_at, INTERVAL %d SECOND)) AS day, SUM(input_tokens + cache_read_tokens + cache_write_tokens) AS input_tokens, SUM(output_tokens) AS output_tokens, SUM(cost_micros) AS cost_micros, COUNT(*) AS calls FROM %i WHERE created_at >= %s GROUP BY day',
				$offset,
				$table,
				$since
			),
			ARRAY_A
		);
		foreach ( (array) $rows as $row ) {
			$key = (string) $row['day'];
			if ( isset( $daily[ $key ] ) ) {
				$daily[ $key ] = array(
					'date'          => $key,
					'input_tokens'  => (int) $row['input_tokens'],
					'output_tokens' => (int) $row['output_tokens'],
					'cost_micros'   => (int) $row['cost_micros'],
					'calls'         => (int) $row['calls'],
				);
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Fixed-shape aggregate over the plugin-owned ledger.
		$by_model = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT provider, model, feature, SUM(input_tokens) AS input_tokens, SUM(output_tokens) AS output_tokens, SUM(cache_read_tokens) AS cache_read_tokens, SUM(cache_write_tokens) AS cache_write_tokens, SUM(cost_micros) AS cost_micros, COUNT(*) AS calls, MAX(estimated) AS estimated FROM %i WHERE created_at >= %s GROUP BY provider, model, feature ORDER BY cost_micros DESC, calls DESC LIMIT 50',
				$table,
				$since
			),
			ARRAY_A
		);

		$breakdown = array();
		foreach ( (array) $by_model as $row ) {
			$breakdown[] = array(
				'provider'           => (string) $row['provider'],
				'model'              => (string) $row['model'],
				'feature'            => (string) $row['feature'],
				'input_tokens'       => (int) $row['input_tokens'],
				'output_tokens'      => (int) $row['output_tokens'],
				'cache_read_tokens'  => (int) $row['cache_read_tokens'],
				'cache_write_tokens' => (int) $row['cache_write_tokens'],
				'cost_micros'        => (int) $row['cost_micros'],
				'calls'              => (int) $row['calls'],
				'estimated'          => (bool) $row['estimated'],
				'priced'             => null !== Pricing::for_model( (string) $row['model'] ),
			);
		}

		$month_start = gmdate( 'Y-m-01 00:00:00', time() + $offset );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Fixed-shape aggregate.
		$month = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT COALESCE(SUM(input_tokens + cache_read_tokens + cache_write_tokens + output_tokens), 0) AS tokens, COALESCE(SUM(cost_micros), 0) AS cost_micros, COUNT(*) AS calls FROM %i WHERE created_at >= %s',
				$table,
				gmdate( 'Y-m-d H:i:s', strtotime( $month_start . ' UTC' ) - $offset )
			),
			ARRAY_A
		);

		$today_key = gmdate( 'Y-m-d', $today );

		return array(
			'days'      => array_values( $daily ),
			'breakdown' => $breakdown,
			'today'     => $daily[ $today_key ] ?? null,
			'month'     => array(
				'tokens'      => (int) ( $month['tokens'] ?? 0 ),
				'cost_micros' => (int) ( $month['cost_micros'] ?? 0 ),
				'calls'       => (int) ( $month['calls'] ?? 0 ),
			),
		);
	}
}
