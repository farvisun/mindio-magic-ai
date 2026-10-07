<?php
/**
 * PSR-18 client over the WordPress HTTP API, used by the Anthropic SDK.
 *
 * In streaming mode the request runs inside a Fiber: every body chunk cURL
 * delivers suspends the Fiber, and the SDK's reads of the response body
 * resume it. Tokens therefore reach the SDK as they arrive, with no second
 * HTTP stack and no direct cURL usage.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI\Transport;

use MindioMagicAI\Vendor\Nyholm\Psr7\Response;
use MindioMagicAI\Vendor\Psr\Http\Client\ClientInterface;
use MindioMagicAI\Vendor\Psr\Http\Message\RequestInterface;
use MindioMagicAI\Vendor\Psr\Http\Message\ResponseInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Psr18Client implements ClientInterface {
	/** Streaming transfers still in progress in this request (diagnostics and tests). */
	private static int $open = 0;

	public static function open_transfers(): int {
		return self::$open;
	}

	public function __construct( private ?Http $http = null, private bool $streaming = false, private ?float $timeout = null ) {}

	public function sendRequest( RequestInterface $request ): ResponseInterface {
		$http    = $this->http ?? new Http();
		$url     = (string) $request->getUri();
		$headers = array();
		foreach ( $request->getHeaders() as $name => $values ) {
			$headers[ (string) $name ] = implode( ', ', $values );
		}
		$stream = $request->getBody();
		if ( $stream->isSeekable() ) {
			$stream->rewind();
		}
		$body = (string) $stream;

		if ( ! $this->streaming ) {
			$result = $http->request( $request->getMethod(), $url, $headers, $body, $this->timeout );
			if ( is_wp_error( $result ) ) {
				throw self::exception( $request, $result ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- PSR-18 exception for the SDK; it is never printed.
			}
			return new Response( $result['status'], $result['headers'], $result['body'] );
		}

		$shared  = (object) array(
			'cancel'  => false,
			'status'  => 0,
			'headers' => array(),
			'result'  => null,
		);
		$timeout = $this->timeout;
		$fiber   = new \Fiber(
			static function () use ( $http, $request, $url, $headers, $body, $shared, $timeout ): void {
				++self::$open;
				try {
					$shared->result = $http->stream(
						$request->getMethod(),
						$url,
						$headers,
						$body,
						static function ( string $chunk, StreamState $state ) use ( $shared ): bool {
							$shared->status  = $state->status;
							$shared->headers = $state->headers;
							if ( ! $shared->cancel ) {
								\Fiber::suspend( $chunk );
							}
							return ! $shared->cancel;
						},
						$timeout
					);
				} finally {
					--self::$open;
				}
			}
		);

		$first = (string) $fiber->start();
		if ( $fiber->isTerminated() ) {
			$result = $shared->result;
			if ( is_wp_error( $result ) ) {
				throw self::exception( $request, $result ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- PSR-18 exception for the SDK; it is never printed.
			}
			return new Response( (int) $result['status'], (array) $result['headers'], $first );
		}

		$cancel = static function () use ( $shared ): void {
			$shared->cancel = true;
		};

		return new Response( $shared->status, $shared->headers, new FiberStream( $fiber, $first, $cancel ) );
	}

	private static function exception( RequestInterface $request, \WP_Error $error ): \RuntimeException {
		if ( in_array( $error->get_error_code(), array( 'private_url', 'https_required', 'invalid_url', 'unresolvable_url', 'url_credentials_forbidden' ), true ) ) {
			return new GuardException( $error );
		}
		return new NetworkException( $request, $error->get_error_message() );
	}
}
