<?php
/**
 * What each known model accepts, so requests never send a parameter a model rejects.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ModelCapabilities {
	/**
	 * @return array{thinking:string,effort:bool,vision:bool,tools:bool,fallbacks:bool,max_output:int}
	 *   thinking: "always" (cannot be configured), "adaptive_default" (on unless changed),
	 *   "adaptive" (opt-in adaptive), or "none".
	 */
	public static function resolve( string $provider, string $model ): array {
		$model = strtolower( $model );

		if ( Presets::PROVIDER_ANTHROPIC === $provider ) {
			return match ( true ) {
				str_starts_with( $model, 'claude-opus-5-5' ) => self::row( 'always', true, true, true, true, 128000 ),
				str_starts_with( $model, 'claude-sonnet-5-5' ) => self::row( 'adaptive_default', true, true, true, true, 128000 ),
				str_starts_with( $model, 'claude-fable-5' ) => self::row( 'always', true, true, true, true, 128000 ),
				str_starts_with( $model, 'claude-opus-5' ), str_starts_with( $model, 'claude-sonnet-5' ) => self::row( 'adaptive_default', true, true, true, false, 128000 ),
				str_starts_with( $model, 'claude-opus-4-' ), str_starts_with( $model, 'claude-sonnet-4-6' ) => self::row( 'adaptive', true, true, true, false, 64000 ),
				str_starts_with( $model, 'claude-haiku-5-5' ) => self::row( 'adaptive_default', true, true, true, false, 128000 ),
				str_starts_with( $model, 'claude-haiku-4-5' ) => self::row( 'none', false, true, true, false, 64000 ),
				default => self::row( 'none', false, true, true, false, 8192 ),
			};
		}

		return self::row( 'none', false, true, true, false, 16384 );
	}

	/** @return array{thinking:string,effort:bool,vision:bool,tools:bool,fallbacks:bool,max_output:int} */
	private static function row( string $thinking, bool $effort, bool $vision, bool $tools, bool $fallbacks, int $max_output ): array {
		return array(
			'thinking'   => $thinking,
			'effort'     => $effort,
			'vision'     => $vision,
			'tools'      => $tools,
			'fallbacks'  => $fallbacks,
			'max_output' => $max_output,
		);
	}
}
