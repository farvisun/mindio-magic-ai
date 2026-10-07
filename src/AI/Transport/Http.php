<?php
/**
 * Outbound HTTP through the WordPress HTTP API, with SSRF checks, DNS pinning, and streaming.
 *
 * Streaming swaps the cURL write callback inside the http_api_curl action,
 * so requests keep WordPress proxy settings, the bundled CA store, and the
 * pre_http_request short-circuit used by tests.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI\Transport;

use MindioMagicAI\Core\UrlGuard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Http {
	/**
	 * @param array<int,string> $private_hosts Exact "host:port" pairs allowed to reach private networks.
	 */
	public function __construct( private array $private_hosts = array(), private float $timeout = 60.0 ) {}

	/**
	 * Send a request and buffer the whole response.
	 *
	 * @param array<string,string> $headers
	 * @return array{status:int,headers:array<string,array<int,string>>,body:string}|\WP_Error
	 */
	public function request( string $method, string $url, array $headers = array(), string $body = '', ?float $timeout = null ): array|\WP_Error {
		$checked = UrlGuard::check( $url, $this->private_hosts );
		if ( is_wp_error( $checked ) ) {
			return $checked;
		}

		$id   = $this->request_id();
		$hook = $this->pin_hook( $id, $checked, null );
		add_action( 'http_api_curl', $hook, 10, 2 );
		try {
			$result = wp_remote_request( $url, $this->args( $id, $method, $headers, $body, $timeout ) );
		} finally {
			remove_action( 'http_api_curl', $hook, 10 );
		}

		if ( is_wp_error( $result ) ) {
			return new \WP_Error( 'http_failed', $result->get_error_message() );
		}

		$out = array();
		foreach ( wp_remote_retrieve_headers( $result )->getAll() as $name => $value ) {
			$out[ strtolower( (string) $name ) ] = array_map( 'strval', (array) $value );
		}

		return array(
			'status'  => (int) wp_remote_retrieve_response_code( $result ),
			'headers' => $out,
			'body'    => (string) wp_remote_retrieve_body( $result ),
		);
	}

	/**
	 * Send a request and hand body chunks to a callback as they arrive.
	 *
	 * The callback returns false to cancel the transfer. Cancellation is checked by
	 * cURL's progress callback, so the connection closes within about a second.
	 *
	 * @param array<string,string>              $headers
	 * @param callable(string,StreamState):bool $on_chunk Receives each chunk and the response status and headers.
	 * @return array{status:int,headers:array<string,array<int,string>>,cancelled:bool}|\WP_Error
	 */
	public function stream( string $method, string $url, array $headers, string $body, callable $on_chunk, ?float $timeout = null ): array|\WP_Error {
		$checked = UrlGuard::check( $url, $this->private_hosts );
		if ( is_wp_error( $checked ) ) {
			return $checked;
		}

		$state = new StreamState();
		$id    = $this->request_id();
		$hook  = $this->pin_hook( $id, $checked, $state );

		$state->on_chunk = $on_chunk;
		add_action( 'http_api_curl', $hook, 10, 2 );
		try {
			$result = wp_remote_request( $url, $this->args( $id, $method, $headers, $body, $timeout ) );
		} finally {
			remove_action( 'http_api_curl', $hook, 10 );
		}

		if ( ! $state->hooked ) {
			// Another transport (or a pre_http_request filter) answered; replay its body as one chunk.
			if ( is_wp_error( $result ) ) {
				return new \WP_Error( 'http_failed', $result->get_error_message() );
			}
			$state->status = (int) wp_remote_retrieve_response_code( $result );
			foreach ( wp_remote_retrieve_headers( $result )->getAll() as $name => $value ) {
				$state->headers[ strtolower( (string) $name ) ] = array_map( 'strval', (array) $value );
			}
			$chunk = (string) wp_remote_retrieve_body( $result );
			if ( '' !== $chunk ) {
				call_user_func( $on_chunk, $chunk, $state );
			}
		} elseif ( 0 === $state->status ) {
			$message = is_wp_error( $result ) ? $result->get_error_message() : __( 'The provider did not respond.', 'mindio-magic-ai' );
			return new \WP_Error( 'http_failed', $message );
		}

		return array(
			'status'    => $state->status,
			'headers'   => $state->headers,
			'cancelled' => $state->cancelled,
		);
	}

	/**
	 * @param array<string,string> $headers
	 * @return array<string,mixed>
	 */
	private function args( string $id, string $method, array $headers, string $body, ?float $timeout ): array {
		return array(
			'method'              => strtoupper( $method ),
			'headers'             => $headers,
			'body'                => '' === $body ? null : $body,
			'timeout'             => $timeout ?? $this->timeout,
			'redirection'         => 0,
			'httpversion'         => '1.1',
			'user-agent'          => 'Mindio-Magic-AI/' . MINDIO_MAGIC_AI_VERSION,
			'reject_unsafe_urls'  => false,
			'decompress'          => true,
			'mindio_ai_request'   => $id,
		);
	}

	/**
	 * @param array{host:string,port:int,ips:array<int,string>} $checked
	 */
	private function pin_hook( string $id, array $checked, ?StreamState $state ): \Closure {
		$resolve = UrlGuard::proxy_active() ? array() : UrlGuard::curl_resolve( $checked );

		return static function ( $handle, $args ) use ( $id, $resolve, $state ): void {
			if ( ! is_array( $args ) || ( $args['mindio_ai_request'] ?? '' ) !== $id ) {
				return;
			}
			if ( $resolve ) {
				curl_setopt( $handle, CURLOPT_RESOLVE, $resolve ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Pins the address validated by UrlGuard on the WordPress-managed handle.
			}
			curl_setopt( $handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Restricts the WordPress-managed handle to HTTP(S).
			if ( null === $state ) {
				return;
			}

			$state->hooked = true;
			// phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Streams the body of the WordPress-managed handle.
			curl_setopt(
				$handle,
				CURLOPT_HEADERFUNCTION,
				static function ( $ch, string $line ) use ( $state ): int {
					unset( $ch );
					$trimmed = trim( $line );
					if ( preg_match( '#^HTTP/\S+\s+(\d{3})#', $trimmed, $match ) ) {
						$state->status  = (int) $match[1];
						$state->headers = array();
					} elseif ( str_contains( $trimmed, ':' ) ) {
						[ $name, $value ]                              = explode( ':', $trimmed, 2 );
						$state->headers[ strtolower( trim( $name ) ) ][] = trim( $value );
					}
					return strlen( $line );
				}
			);
			curl_setopt(
				$handle,
				CURLOPT_WRITEFUNCTION,
				static function ( $ch, string $data ) use ( $state ): int {
					unset( $ch );
					if ( ! $state->cancelled && false === call_user_func( $state->on_chunk, $data, $state ) ) {
						$state->cancelled = true;
					}
					// Always accept the bytes: a short write makes WordPress retry the whole request.
					return strlen( $data );
				}
			);
			curl_setopt( $handle, CURLOPT_NOPROGRESS, false );
			curl_setopt( $handle, CURLOPT_XFERINFOFUNCTION, static fn(): int => $state->cancelled ? 1 : 0 );
			// phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_setopt
		};
	}

	private function request_id(): string {
		return bin2hex( random_bytes( 8 ) );
	}
}
