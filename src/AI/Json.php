<?php
/**
 * JSON helpers that keep empty objects as objects.
 *
 * Provider payloads distinguish {} from []: a tool call with no arguments must
 * round-trip as an object, or the provider rejects the replayed history.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Json {
	/**
	 * Decode to associative arrays, except that empty JSON objects stay \stdClass.
	 */
	public static function decode( string $json ): mixed {
		if ( '' === $json ) {
			return null;
		}
		$decoded = json_decode( $json, false );
		return self::convert( $decoded );
	}

	public static function encode( mixed $value ): string {
		return (string) wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Turn tool input into an object-shaped value: arrays with keys stay arrays, an empty array becomes {}.
	 */
	public static function object( mixed $value ): mixed {
		if ( is_array( $value ) && ! $value ) {
			return new \stdClass();
		}
		if ( $value instanceof \stdClass && ! get_object_vars( $value ) ) {
			return $value;
		}
		return is_array( $value ) ? $value : new \stdClass();
	}

	/**
	 * Strictly parse streamed tool input. Returns null when it is not a JSON object.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function parse_object( string $json ): ?array {
		$json = trim( $json );
		if ( '' === $json ) {
			return array();
		}
		try {
			$decoded = json_decode( $json, true, 64, JSON_THROW_ON_ERROR );
		} catch ( \JsonException ) {
			return null;
		}
		if ( ! is_array( $decoded ) || ( $decoded && array_is_list( $decoded ) ) ) {
			return null;
		}
		return $decoded;
	}

	private static function convert( mixed $value ): mixed {
		if ( $value instanceof \stdClass ) {
			$vars = get_object_vars( $value );
			if ( ! $vars ) {
				return $value;
			}
			$out = array();
			foreach ( $vars as $key => $child ) {
				$out[ $key ] = self::convert( $child );
			}
			return $out;
		}
		if ( is_array( $value ) ) {
			return array_map( array( self::class, 'convert' ), $value );
		}
		return $value;
	}
}
