<?php
/**
 * Per-model token prices used to estimate cost. Administrators can edit them.
 *
 * Prices are US dollars per million tokens. Models without a price are
 * recorded with zero cost and flagged as unpriced in usage reports.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Pricing {
	public const OPTION = 'mindio_magic_ai_prices';

	/** @return array<string,array{input:float,output:float,cache_read:float,cache_write:float}> */
	public static function defaults(): array {
		return array(
			'claude-fable-5-1'  => array( 'input' => 10.0, 'output' => 50.0, 'cache_read' => 0.25, 'cache_write' => 12.5 ),
			'claude-opus-5-5'   => array( 'input' => 4.0, 'output' => 20.0, 'cache_read' => 0.2, 'cache_write' => 5.0 ),
			'claude-sonnet-5-5' => array( 'input' => 2.0, 'output' => 10.0, 'cache_read' => 0.2, 'cache_write' => 2.5 ),
			// Prompts up to 100K tokens; longer prompts cost more, so estimates for them run low.
			'claude-haiku-5-5'  => array( 'input' => 0.1, 'output' => 0.5, 'cache_read' => 0.01, 'cache_write' => 0.125 ),
			'claude-haiku-4-5'  => array( 'input' => 1.0, 'output' => 5.0, 'cache_read' => 0.1, 'cache_write' => 1.25 ),
		);
	}

	/** @return array<string,array{input:float,output:float,cache_read:float,cache_write:float}> */
	public static function all(): array {
		$stored = get_option( self::OPTION, null );
		return is_array( $stored ) ? $stored : self::defaults();
	}

	/**
	 * @param array<string,mixed> $input
	 * @return array<string,array{input:float,output:float,cache_read:float,cache_write:float}>
	 */
	public static function update( array $input ): array {
		$clean = array();
		foreach ( $input as $model => $row ) {
			$model = Settings::model_id( $model );
			if ( '' === $model || ! is_array( $row ) ) {
				continue;
			}
			$clean[ $model ] = array(
				'input'       => self::price( $row['input'] ?? 0 ),
				'output'      => self::price( $row['output'] ?? 0 ),
				'cache_read'  => self::price( $row['cache_read'] ?? 0 ),
				'cache_write' => self::price( $row['cache_write'] ?? 0 ),
			);
		}
		update_option( self::OPTION, $clean, false );
		return $clean;
	}

	/**
	 * Find a price row by exact id, then by the longest matching prefix (dated snapshot ids).
	 *
	 * @return array{input:float,output:float,cache_read:float,cache_write:float}|null
	 */
	public static function for_model( string $model ): ?array {
		$prices = self::all();
		$model  = strtolower( $model );
		if ( isset( $prices[ $model ] ) ) {
			return $prices[ $model ];
		}
		$best = null;
		$len  = 0;
		foreach ( $prices as $id => $row ) {
			$id = strtolower( (string) $id );
			if ( str_starts_with( $model, $id ) && strlen( $id ) > $len ) {
				$best = $row;
				$len  = strlen( $id );
			}
		}
		return $best;
	}

	/**
	 * Cost in micro-dollars (one millionth of a dollar).
	 *
	 * @param array{input?:int,output?:int,cache_read?:int,cache_write?:int} $usage
	 */
	public static function cost_micros( string $model, array $usage ): ?int {
		$row = self::for_model( $model );
		if ( null === $row ) {
			return null;
		}
		// tokens × (dollars per million tokens) = micro-dollars.
		$micros = ( (int) ( $usage['input'] ?? 0 ) ) * (float) $row['input']
			+ ( (int) ( $usage['output'] ?? 0 ) ) * (float) $row['output']
			+ ( (int) ( $usage['cache_read'] ?? 0 ) ) * (float) $row['cache_read']
			+ ( (int) ( $usage['cache_write'] ?? 0 ) ) * (float) $row['cache_write'];
		return (int) ceil( $micros );
	}

	private static function price( mixed $value ): float {
		return round( max( 0.0, min( 10000.0, (float) $value ) ), 4 );
	}
}
