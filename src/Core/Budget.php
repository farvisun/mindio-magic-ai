<?php
/**
 * Per-feature spending limits with reserve-then-settle accounting.
 *
 * Before a provider call the worst-case cost is reserved atomically, so
 * concurrent or streamed calls cannot overrun a hard limit. After the call
 * the reservation is replaced by the actual usage.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Budget {
	public const OPTION = 'mindio_magic_ai_budgets';

	public const FEATURES = array( 'assistant' );

	/** @return array<string,array{daily_tokens:int,monthly_cost:float}> */
	public static function all(): array {
		$stored = get_option( self::OPTION, array() );
		$out    = array();
		foreach ( self::FEATURES as $feature ) {
			$row             = is_array( $stored[ $feature ] ?? null ) ? $stored[ $feature ] : array();
			$out[ $feature ] = array(
				'daily_tokens' => absint( $row['daily_tokens'] ?? 0 ),
				'monthly_cost' => round( max( 0.0, (float) ( $row['monthly_cost'] ?? 0 ) ), 2 ),
			);
		}
		return $out;
	}

	/**
	 * @param array<string,mixed> $input
	 * @return array<string,array{daily_tokens:int,monthly_cost:float}>
	 */
	public static function update( array $input ): array {
		$current = self::all();
		foreach ( $input as $feature => $row ) {
			if ( ! isset( $current[ $feature ] ) || ! is_array( $row ) ) {
				continue;
			}
			$current[ $feature ] = array(
				'daily_tokens' => min( 1000000000, absint( $row['daily_tokens'] ?? 0 ) ),
				'monthly_cost' => round( max( 0.0, min( 1000000.0, (float) ( $row['monthly_cost'] ?? 0 ) ) ), 2 ),
			);
		}
		update_option( self::OPTION, $current, false );
		return $current;
	}

	/**
	 * Reserve the worst case for one call.
	 *
	 * @return array{feature:string,tokens:int,cost:int,day:string,month:string}|\WP_Error
	 */
	public static function reserve( string $feature, int $tokens, int $cost_micros ): array|\WP_Error {
		if ( KillSwitch::active() ) {
			return new \WP_Error( 'kill_switch', __( 'AI features are paused by an administrator.', 'mindio-magic-ai' ), array( 'status' => 503 ) );
		}

		$limits = self::all()[ $feature ] ?? array( 'daily_tokens' => 0, 'monthly_cost' => 0.0 );
		$day    = self::day_key();
		$month  = self::month_key();
		$token_bucket = 'budget:' . $feature . ':tokens:' . $day;
		$cost_bucket  = 'budget:' . $feature . ':cost:' . $month;

		if ( $limits['daily_tokens'] > 0 && ! Counters::try_add( $token_bucket, $tokens, $limits['daily_tokens'], 2 * DAY_IN_SECONDS ) ) {
			return new \WP_Error( 'budget_exceeded', __( 'The daily token budget for this feature is used up. An administrator can raise it under Usage.', 'mindio-magic-ai' ), array( 'status' => 429 ) );
		}
		if ( $limits['daily_tokens'] <= 0 ) {
			Counters::add( $token_bucket, $tokens, 2 * DAY_IN_SECONDS );
		}

		$cost_limit = (int) round( $limits['monthly_cost'] * 1000000 );
		if ( $cost_limit > 0 && ! Counters::try_add( $cost_bucket, $cost_micros, $cost_limit, 32 * DAY_IN_SECONDS ) ) {
			Counters::add( $token_bucket, -$tokens, 2 * DAY_IN_SECONDS );
			return new \WP_Error( 'budget_exceeded', __( 'The monthly cost budget for this feature is used up. An administrator can raise it under Usage.', 'mindio-magic-ai' ), array( 'status' => 429 ) );
		}
		if ( $cost_limit <= 0 ) {
			Counters::add( $cost_bucket, $cost_micros, 32 * DAY_IN_SECONDS );
		}

		return array(
			'feature' => $feature,
			'tokens'  => $tokens,
			'cost'    => $cost_micros,
			'day'     => $day,
			'month'   => $month,
		);
	}

	/**
	 * Replace a reservation with the actual usage.
	 *
	 * @param array{feature:string,tokens:int,cost:int,day:string,month:string} $reservation
	 */
	public static function settle( array $reservation, int $tokens, int $cost_micros ): void {
		$feature = $reservation['feature'];
		Counters::add( 'budget:' . $feature . ':tokens:' . $reservation['day'], $tokens - $reservation['tokens'], 2 * DAY_IN_SECONDS );
		Counters::add( 'budget:' . $feature . ':cost:' . $reservation['month'], $cost_micros - $reservation['cost'], 32 * DAY_IN_SECONDS );
		self::maybe_alert( $feature );
	}

	/** @return array<string,array{tokens_today:int,cost_month:int}> */
	public static function consumption(): array {
		$out = array();
		foreach ( self::FEATURES as $feature ) {
			$out[ $feature ] = array(
				'tokens_today' => max( 0, Counters::get( 'budget:' . $feature . ':tokens:' . self::day_key() ) ),
				'cost_month'   => max( 0, Counters::get( 'budget:' . $feature . ':cost:' . self::month_key() ) ),
			);
		}
		return $out;
	}

	private static function maybe_alert( string $feature ): void {
		$limits = self::all()[ $feature ] ?? null;
		if ( ! $limits ) {
			return;
		}
		$used  = self::consumption()[ $feature ];
		$ratio = 0.0;
		if ( $limits['daily_tokens'] > 0 ) {
			$ratio = max( $ratio, $used['tokens_today'] / $limits['daily_tokens'] );
		}
		if ( $limits['monthly_cost'] > 0 ) {
			$ratio = max( $ratio, $used['cost_month'] / ( $limits['monthly_cost'] * 1000000 ) );
		}
		if ( $ratio < 0.8 ) {
			return;
		}

		$flag = 'budget:' . $feature . ':alerted:' . self::day_key();
		if ( Counters::get( $flag ) > 0 ) {
			return;
		}
		Counters::add( $flag, 1, 2 * DAY_IN_SECONDS );

		$to = (string) Settings::get( 'alert_email' );
		$to = '' !== $to ? $to : (string) get_option( 'admin_email' );
		if ( ! is_email( $to ) ) {
			return;
		}
		wp_mail(
			$to,
			sprintf(
				/* translators: %s: site name. */
				__( '[%s] Mindio AI budget warning', 'mindio-magic-ai' ),
				wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES )
			),
			sprintf(
				/* translators: 1: feature name, 2: percentage used. */
				__( 'The %1$s feature has used %2$d%% of its budget. Review usage in WordPress under Mindio AI → Usage.', 'mindio-magic-ai' ),
				$feature,
				(int) floor( $ratio * 100 )
			)
		);
		AuditLog::write( 'budget.alert', 'feature', $feature, array( 'ratio' => round( $ratio, 2 ) ) );
	}

	private static function day_key(): string {
		return wp_date( 'Ymd' );
	}

	private static function month_key(): string {
		return wp_date( 'Ym' );
	}
}
