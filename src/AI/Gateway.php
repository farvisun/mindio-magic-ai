<?php
/**
 * Single entry point for model calls: kill switch, budget reservation, the call, and usage accounting.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI;

use MindioMagicAI\AI\Providers\AnthropicProvider;
use MindioMagicAI\AI\Providers\OpenAICompatibleProvider;
use MindioMagicAI\Core\Budget;
use MindioMagicAI\Core\KillSwitch;
use MindioMagicAI\Core\Pricing;
use MindioMagicAI\Core\UsageLedger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Gateway {
	public static function provider( Connection $connection ): Provider {
		$provider = Presets::PROVIDER_ANTHROPIC === $connection->provider
			? new AnthropicProvider( $connection )
			: new OpenAICompatibleProvider( $connection );

		/**
		 * Replace the provider adapter for a connection, for example with a test double.
		 *
		 * @param Provider   $provider
		 * @param Connection $connection
		 */
		return apply_filters( 'mindio_magic_ai_provider', $provider, $connection );
	}

	/**
	 * @param callable(array<string,mixed>):bool|null $on_event
	 */
	public static function chat( Connection $connection, ChatRequest $request, string $feature, int $run_id = 0, ?callable $on_event = null ): ChatResponse|\WP_Error {
		if ( KillSwitch::active() ) {
			return new \WP_Error( 'kill_switch', __( 'AI features are paused by an administrator.', 'mindio-magic-ai' ), array( 'status' => 503 ) );
		}

		$input_estimate = $request->estimated_input_tokens();
		$reserve_tokens = $input_estimate + $request->max_tokens;
		$reserve_cost   = (int) Pricing::cost_micros(
			$request->model,
			array(
				'input'  => $input_estimate,
				'output' => $request->max_tokens,
			)
		);

		$reservation = Budget::reserve( $feature, $reserve_tokens, $reserve_cost );
		if ( is_wp_error( $reservation ) ) {
			return $reservation;
		}

		$response = self::provider( $connection )->chat( $request, $on_event );

		if ( is_wp_error( $response ) ) {
			Budget::settle( $reservation, 0, 0 );
			return $response;
		}

		$cost = Pricing::cost_micros( $response->model, $response->usage ) ?? 0;
		Budget::settle( $reservation, $response->total_tokens(), $cost );
		UsageLedger::record( $feature, $connection->id, $connection->provider, $response->model, $run_id, $response->usage, $cost, $response->usage_estimated );

		return $response;
	}
}
