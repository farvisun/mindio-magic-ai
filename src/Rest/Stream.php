<?php
/**
 * Server-sent events over the REST response, with the buffering work-arounds shared hosts need.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Rest;

use MindioMagicAI\AI\Json;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Stream {
	public static function open(): void {
		ignore_user_abort( true );
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged -- Disabled on some hosts; the run enforces its own wall-clock limit.
		}
		if ( session_status() === PHP_SESSION_ACTIVE ) {
			session_write_close();
		}
		@ini_set( 'zlib.output_compression', '0' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.PHP.IniSet.Risky,Squiz.PHP.DiscouragedFunctions.Discouraged -- Compression buffers the whole stream.
		@ini_set( 'output_buffering', '0' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.PHP.IniSet.Risky,Squiz.PHP.DiscouragedFunctions.Discouraged -- Buffering delays events.
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', '1' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Not available on every server.
		}
		while ( ob_get_level() > 0 ) {
			ob_end_flush();
		}

		if ( ! headers_sent() ) {
			status_header( 200 );
			header( 'Content-Type: text/event-stream; charset=utf-8' );
			header( 'Cache-Control: no-store, no-cache, no-transform' );
			header( 'X-Accel-Buffering: no' );
			header( 'X-LiteSpeed-Cache-Control: no-cache' );
			header( 'Content-Encoding: identity' );
			nocache_headers();
		}

		// Some proxies hold the first few kilobytes; padding pushes the stream through them.
		echo ':' . str_repeat( ' ', 2048 ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SSE comment.
		flush();
	}

	/**
	 * @param array<string,mixed> $payload
	 */
	public static function write( int $id, string $type, array $payload ): void {
		echo 'id: ' . (int) $id . "\nevent: " . preg_replace( '/[^a-z_]/', '', $type ) . "\ndata: " . Json::encode( $payload ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded SSE frame.
		flush();
	}

	public static function close(): void {
		flush();
		exit;
	}
}
