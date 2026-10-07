<?php
/**
 * Fixed-window rate limits backed by atomic counters.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RateLimiter {
	/**
	 * @return array{allowed:bool,limit:int,remaining:int,retry_after:int}
	 */
	public static function consume( string $bucket, string $identity, int $limit, int $window ): array {
		$window = max( 1, $window );
		$slot   = (int) floor( time() / $window );
		$key    = 'rl:' . $bucket . ':' . hash( 'sha256', $identity ) . ':' . $slot;

		$allowed = Counters::try_add( $key, 1, max( 1, $limit ), $window + 5 );
		$count   = Counters::get( $key );

		return array(
			'allowed'     => $allowed,
			'limit'       => $limit,
			'remaining'   => max( 0, $limit - $count ),
			'retry_after' => max( 1, $window - ( time() % $window ) ),
		);
	}
}
