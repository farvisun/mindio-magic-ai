<?php
/**
 * Plugin settings with a fixed schema and defaults.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Settings {
	public const OPTION = 'mindio_magic_ai_settings';

	public const EFFORTS = array( 'low', 'medium', 'high', 'xhigh', 'max' );

	/** @return array<string,mixed> */
	public static function defaults(): array {
		return array(
			'assistant_connection'        => 0,
			'assistant_model'             => '',
			'effort'                      => 'medium',
			'max_steps'                   => 12,
			'max_wall_seconds'            => 120,
			'max_output_tokens'           => 8000,
			'allow_auto_apply'            => false,
			'proposal_ttl_minutes'        => 60,
			'conversation_retention_days' => 90,
			'audit_retention_days'        => 90,
			'link_allowlist'              => array(),
			'private_hosts'               => array(),
			'stream_transport'            => 'auto',
			'alert_email'                 => '',
			'delete_on_uninstall'         => false,
		);
	}

	public static function ensure_defaults(): void {
		$current = get_option( self::OPTION, array() );
		$merged  = wp_parse_args( is_array( $current ) ? $current : array(), self::defaults() );
		if ( $merged !== $current ) {
			update_option( self::OPTION, $merged, false );
		}
	}

	/** @return array<string,mixed> */
	public static function all(): array {
		$stored = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );
	}

	public static function get( string $key ): mixed {
		$all = self::all();
		return $all[ $key ] ?? null;
	}

	/**
	 * Merge, sanitize, and store a partial settings update.
	 *
	 * @param array<string,mixed> $input
	 * @return array<string,mixed> The stored settings.
	 */
	public static function update( array $input ): array {
		$current = self::all();
		$next    = $current;

		foreach ( $input as $key => $value ) {
			if ( ! array_key_exists( $key, $current ) ) {
				continue;
			}
			$next[ $key ] = self::sanitize( (string) $key, $value );
		}

		update_option( self::OPTION, $next, false );
		return $next;
	}

	private static function sanitize( string $key, mixed $value ): mixed {
		return match ( $key ) {
			'assistant_connection'        => absint( $value ),
			'assistant_model'             => self::model_id( $value ),
			'effort'                      => in_array( $value, self::EFFORTS, true ) ? $value : 'medium',
			'max_steps'                   => max( 1, min( 40, absint( $value ) ) ),
			'max_wall_seconds'            => max( 20, min( 900, absint( $value ) ) ),
			'max_output_tokens'           => max( 256, min( 64000, absint( $value ) ) ),
			'proposal_ttl_minutes'        => max( 5, min( 10080, absint( $value ) ) ),
			'conversation_retention_days' => min( 3650, absint( $value ) ),
			'audit_retention_days'        => max( 7, min( 3650, absint( $value ) ) ),
			'link_allowlist'              => self::host_list( $value, false ),
			'private_hosts'               => self::host_list( $value, true ),
			'stream_transport'            => in_array( $value, array( 'auto', 'sse', 'poll' ), true ) ? $value : 'auto',
			'alert_email'                 => (string) sanitize_email( (string) $value ),
			default                       => (bool) $value,
		};
	}

	public static function model_id( mixed $value ): string {
		$value = trim( (string) $value );
		return (string) preg_replace( '/[^A-Za-z0-9._:\/@-]/', '', substr( $value, 0, 100 ) );
	}

	/**
	 * Normalize a list of hosts. With ports, entries are exact "host:port" pairs.
	 *
	 * @return array<int,string>
	 */
	public static function host_list( mixed $value, bool $with_port ): array {
		$items = is_array( $value ) ? $value : preg_split( '/[\s,]+/', (string) $value );
		$out   = array();
		foreach ( (array) $items as $item ) {
			$item = strtolower( trim( (string) $item ) );
			if ( '' === $item ) {
				continue;
			}
			if ( $with_port ) {
				if ( preg_match( '/^(\[[0-9a-f:.]+\]|[a-z0-9.-]+):(\d{1,5})$/', $item, $match ) && (int) $match[2] > 0 && (int) $match[2] <= 65535 ) {
					$out[] = $match[1] . ':' . (int) $match[2];
				}
				continue;
			}
			if ( preg_match( '/^(\*\.)?[a-z0-9.-]+$/', $item ) ) {
				$out[] = $item;
			}
		}
		return array_values( array_unique( $out ) );
	}
}
