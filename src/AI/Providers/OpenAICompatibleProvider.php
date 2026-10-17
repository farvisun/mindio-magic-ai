<?php
/**
 * OpenAI Chat Completions and compatible APIs (DeepSeek, Gemini's OpenAI endpoint, and custom gateways).
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI\Providers;

use MindioMagicAI\AI\ChatRequest;
use MindioMagicAI\AI\ChatResponse;
use MindioMagicAI\AI\Connection;
use MindioMagicAI\AI\Json;
use MindioMagicAI\AI\Presets;
use MindioMagicAI\AI\Provider;
use MindioMagicAI\AI\Transport\Http;
use MindioMagicAI\AI\Transport\SseParser;
use MindioMagicAI\Core\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OpenAICompatibleProvider implements Provider {
	public function __construct( private Connection $connection ) {}

	public function chat( ChatRequest $request, ?callable $on_event = null ): ChatResponse|\WP_Error {
		$key = $this->connection->api_key();
		if ( '' === $key ) {
			return new \WP_Error( 'missing_key', __( 'This connection has no API key.', 'mindio-magic-ai' ), array( 'status' => 400 ) );
		}

		$streaming = null !== $on_event;
		$body      = array(
			'model'    => $request->model,
			'messages' => $this->wire_messages( $request ),
		);
		$body[ $this->connection->max_tokens_param() ] = $request->max_tokens;
		if ( $request->tools ) {
			$body['tools']       = array_map(
				static fn( array $tool ): array => array(
					'type'     => 'function',
					'function' => array(
						'name'        => $tool['name'],
						'description' => $tool['description'],
						'parameters'  => Json::object( $tool['schema'] ),
					),
				),
				$request->tools
			);
			$body['tool_choice'] = 'auto';
		}
		if ( '' !== $request->user_hash && 'openai' === $this->connection->preset ) {
			$body['user'] = $request->user_hash;
		}
		if ( $streaming ) {
			$body['stream']         = true;
			$body['stream_options'] = array( 'include_usage' => true );
		}

		$http    = $this->http();
		$url     = $this->connection->base_url . '/chat/completions';
		$headers = $this->headers( $key );
		$payload = Json::encode( $body );

		if ( ! $streaming ) {
			$result = $http->request( 'POST', $url, $headers, $payload );
			if ( is_wp_error( $result ) ) {
				return $this->network_error( $result );
			}
			if ( $result['status'] >= 400 ) {
				return $this->status_error( $result['status'], $result['body'] );
			}
			$decoded = json_decode( $result['body'], true );
			if ( ! is_array( $decoded ) ) {
				return new \WP_Error( 'provider_bad_response', __( 'The provider returned an unreadable response.', 'mindio-magic-ai' ), array( 'status' => 502 ) );
			}
			return $this->from_completion( $decoded, $request );
		}

		$parser    = new SseParser();
		$state     = array(
			'text'      => '',
			'tools'     => array(),
			'finish'    => '',
			'usage'     => null,
			'model'     => $request->model,
			'error'     => '',
			'cancelled' => false,
		);
		$error_body = '';

		$handle = function ( array $event ) use ( &$state, $on_event ): bool {
			$data = trim( $event['data'] );
			if ( '[DONE]' === $data || '' === $data ) {
				return true;
			}
			$chunk = json_decode( $data, true );
			if ( ! is_array( $chunk ) ) {
				return true;
			}
			if ( isset( $chunk['error'] ) ) {
				$state['error'] = (string) ( $chunk['error']['message'] ?? 'error' );
				return true;
			}
			if ( ! empty( $chunk['model'] ) ) {
				$state['model'] = (string) $chunk['model'];
			}
			if ( isset( $chunk['usage'] ) && is_array( $chunk['usage'] ) ) {
				$state['usage'] = $chunk['usage'];
			}
			foreach ( (array) ( $chunk['choices'] ?? array() ) as $choice ) {
				$delta = (array) ( $choice['delta'] ?? array() );
				if ( isset( $delta['content'] ) && '' !== (string) $delta['content'] ) {
					$state['text'] .= (string) $delta['content'];
					if ( false === $on_event(
						array(
							'type' => 'text',
							'text' => (string) $delta['content'],
						)
					) ) {
						$state['cancelled'] = true;
						return false;
					}
				}
				foreach ( (array) ( $delta['tool_calls'] ?? array() ) as $call ) {
					$index = (int) ( $call['index'] ?? 0 );
					if ( ! isset( $state['tools'][ $index ] ) ) {
						$state['tools'][ $index ] = array(
							'id'        => '',
							'name'      => '',
							'arguments' => '',
						);
					}
					if ( ! empty( $call['id'] ) ) {
						$state['tools'][ $index ]['id'] = (string) $call['id'];
					}
					if ( ! empty( $call['function']['name'] ) ) {
						$state['tools'][ $index ]['name'] .= (string) $call['function']['name'];
						$on_event(
							array(
								'type' => 'tool_start',
								'name' => $state['tools'][ $index ]['name'],
							)
						);
					}
					if ( isset( $call['function']['arguments'] ) ) {
						$state['tools'][ $index ]['arguments'] .= (string) $call['function']['arguments'];
					}
				}
				if ( ! empty( $choice['finish_reason'] ) ) {
					$state['finish'] = (string) $choice['finish_reason'];
				}
			}
			return true;
		};

		$result = $http->stream(
			'POST',
			$url,
			$headers,
			$payload,
			static function ( string $chunk, $stream_state ) use ( $parser, $handle, &$error_body ): bool {
				if ( $stream_state->status >= 400 ) {
					$error_body .= $chunk;
					return strlen( $error_body ) < 65536;
				}
				foreach ( $parser->feed( $chunk ) as $event ) {
					if ( false === $handle( $event ) ) {
						return false;
					}
				}
				return true;
			}
		);

		if ( is_wp_error( $result ) ) {
			return $this->network_error( $result );
		}
		if ( $result['status'] >= 400 ) {
			return $this->status_error( $result['status'], $error_body );
		}
		foreach ( $parser->finish() as $event ) {
			$handle( $event );
		}
		if ( '' !== $state['error'] && '' === $state['text'] && ! $state['tools'] ) {
			return new \WP_Error( 'provider_error', $this->clean( $state['error'] ), array( 'status' => 502 ) );
		}

		$message = array(
			'role'    => 'assistant',
			'content' => '' === $state['text'] ? null : $state['text'],
		);
		if ( $state['tools'] ) {
			ksort( $state['tools'] );
			$message['tool_calls'] = array_values(
				array_map(
					static fn( array $tool ): array => array(
						'id'       => '' === $tool['id'] ? 'call_' . wp_generate_password( 12, false ) : $tool['id'],
						'type'     => 'function',
						'function' => array(
							'name'      => $tool['name'],
							'arguments' => '' === $tool['arguments'] ? '{}' : $tool['arguments'],
						),
					),
					$state['tools']
				)
			);
		}

		return $this->build(
			$message,
			$state['cancelled'] ? 'cancelled' : $state['finish'],
			is_array( $state['usage'] ) ? $state['usage'] : null,
			$state['model'],
			$request
		);
	}

	public function list_models(): array|\WP_Error {
		$key = $this->connection->api_key();
		if ( '' === $key ) {
			return new \WP_Error( 'missing_key', __( 'This connection has no API key.', 'mindio-magic-ai' ), array( 'status' => 400 ) );
		}
		$headers = $this->headers( $key );
		unset( $headers['Content-Type'] );
		$result = $this->http()->request( 'GET', $this->connection->base_url . '/models', $headers );
		if ( is_wp_error( $result ) ) {
			return $this->network_error( $result );
		}
		if ( $result['status'] >= 400 ) {
			return $this->status_error( $result['status'], $result['body'] );
		}
		$decoded = json_decode( $result['body'], true );
		$ids     = array();
		foreach ( (array) ( $decoded['data'] ?? $decoded['models'] ?? array() ) as $model ) {
			$id = is_array( $model ) ? (string) ( $model['id'] ?? $model['name'] ?? '' ) : (string) $model;
			$id = preg_replace( '#^models/#', '', $id );
			if ( '' !== $id ) {
				$ids[] = $id;
			}
		}
		sort( $ids );
		return $ids;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function wire_messages( ChatRequest $request ): array {
		$system = $request->system . ( '' !== $request->context ? "\n\n" . $request->context : '' );
		$out    = array(
			array(
				'role'    => 'system',
				'content' => $system,
			),
		);

		foreach ( $request->messages as $message ) {
			$role = (string) $message['role'];
			if ( 'assistant' === $role ) {
				$raw = $message['raw'] ?? null;
				if ( Presets::PROVIDER_OPENAI === ( $message['provider'] ?? '' ) && is_array( $raw ) && isset( $raw['role'] ) ) {
					$out[] = $raw;
					continue;
				}
				$text  = '';
				$calls = array();
				foreach ( (array) $message['content'] as $block ) {
					if ( 'text' === $block['type'] ) {
						$text .= (string) $block['text'];
					} elseif ( 'tool_call' === $block['type'] ) {
						$calls[] = array(
							'id'       => (string) $block['id'],
							'type'     => 'function',
							'function' => array(
								'name'      => (string) $block['name'],
								'arguments' => Json::encode( Json::object( $block['input'] ?? array() ) ),
							),
						);
					}
				}
				$entry = array(
					'role'    => 'assistant',
					'content' => '' === $text ? null : $text,
				);
				if ( $calls ) {
					$entry['tool_calls'] = $calls;
				}
				if ( null !== $entry['content'] || $calls ) {
					$out[] = $entry;
				}
			} elseif ( 'tool' === $role ) {
				foreach ( (array) $message['content'] as $block ) {
					if ( 'tool_result' === $block['type'] ) {
						$out[] = array(
							'role'         => 'tool',
							'tool_call_id' => (string) $block['id'],
							'content'      => (string) $block['content'],
						);
					}
				}
			} else {
				$parts    = array();
				$has_file = false;
				foreach ( (array) $message['content'] as $block ) {
					if ( 'text' === $block['type'] && '' !== trim( (string) $block['text'] ) ) {
						$parts[] = array(
							'type' => 'text',
							'text' => (string) $block['text'],
						);
					} elseif ( 'image' === $block['type'] && ! empty( $block['data'] ) ) {
						$has_file = true;
						$parts[]  = array(
							'type'      => 'image_url',
							'image_url' => array( 'url' => 'data:' . $block['media_type'] . ';base64,' . $block['data'] ),
						);
					}
				}
				if ( ! $parts ) {
					continue;
				}
				$out[] = array(
					'role'    => 'user',
					'content' => $has_file ? $parts : implode( "\n\n", array_column( $parts, 'text' ) ),
				);
			}
		}

		return $out;
	}

	/** @param array<string,mixed> $completion */
	private function from_completion( array $completion, ChatRequest $request ): ChatResponse {
		$choice  = (array) ( $completion['choices'][0] ?? array() );
		$message = (array) ( $choice['message'] ?? array() );
		return $this->build(
			array_intersect_key( $message, array_flip( array( 'role', 'content', 'tool_calls' ) ) ) + array( 'role' => 'assistant' ),
			(string) ( $choice['finish_reason'] ?? '' ),
			is_array( $completion['usage'] ?? null ) ? $completion['usage'] : null,
			(string) ( $completion['model'] ?? $request->model ),
			$request
		);
	}

	/**
	 * @param array<string,mixed>      $message
	 * @param array<string,mixed>|null $usage
	 */
	private function build( array $message, string $finish, ?array $usage, string $model, ChatRequest $request ): ChatResponse {
		$content = array();
		$text    = is_string( $message['content'] ?? null ) ? (string) $message['content'] : '';
		if ( '' !== $text ) {
			$content[] = array(
				'type' => 'text',
				'text' => $text,
			);
		}
		foreach ( (array) ( $message['tool_calls'] ?? array() ) as $call ) {
			$arguments = (string) ( $call['function']['arguments'] ?? '' );
			$parsed    = Json::parse_object( $arguments );
			$content[] = array(
				'type'  => 'tool_call',
				'id'    => (string) ( $call['id'] ?? '' ),
				'name'  => (string) ( $call['function']['name'] ?? '' ),
				'input' => $parsed ?? array(),
				'valid' => null !== $parsed,
			);
		}

		$stop = match ( $finish ) {
			'stop'                     => ChatResponse::STOP_END,
			'tool_calls', 'function_call' => ChatResponse::STOP_TOOL_USE,
			'length'                   => ChatResponse::STOP_MAX,
			'content_filter'           => ChatResponse::STOP_REFUSAL,
			'cancelled'                => ChatResponse::STOP_CANCELLED,
			default                    => ! empty( $message['tool_calls'] ) ? ChatResponse::STOP_TOOL_USE : ChatResponse::STOP_END,
		};

		$estimated = null === $usage;
		$cached    = (int) ( $usage['prompt_tokens_details']['cached_tokens'] ?? 0 );
		$counts    = array(
			'input'       => $estimated ? $request->estimated_input_tokens() : max( 0, (int) ( $usage['prompt_tokens'] ?? 0 ) - $cached ),
			'output'      => $estimated ? (int) ceil( mb_strlen( $text . Json::encode( $message['tool_calls'] ?? array() ) ) / 3.5 ) : (int) ( $usage['completion_tokens'] ?? 0 ),
			'cache_read'  => $cached,
			'cache_write' => 0,
		);

		return new ChatResponse( $content, $message, $stop, $counts, $model, $estimated );
	}

	/** @return array<string,string> */
	private function headers( string $key ): array {
		$headers = array_merge(
			$this->connection->extra_headers(),
			array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $key,
				'Accept'        => 'application/json, text/event-stream',
			)
		);
		return $headers;
	}

	private function http(): Http {
		return new Http( (array) Settings::get( 'private_hosts' ), $this->connection->timeout() );
	}

	private function network_error( \WP_Error $error ): \WP_Error {
		if ( in_array( $error->get_error_code(), array( 'private_url', 'https_required', 'invalid_url', 'unresolvable_url' ), true ) ) {
			return $error;
		}
		return new \WP_Error( 'provider_unavailable', __( 'Could not reach the provider. Check the base URL and the server’s outbound connection.', 'mindio-magic-ai' ) . ' ' . $this->clean( $error->get_error_message() ), array( 'status' => 502 ) );
	}

	private function status_error( int $status, string $body ): \WP_Error {
		$decoded = json_decode( $body, true );
		$detail  = '';
		if ( is_array( $decoded ) ) {
			$error  = $decoded['error'] ?? $decoded;
			$detail = is_array( $error ) ? (string) ( $error['message'] ?? '' ) : (string) $error;
			if ( is_array( $decoded[0]['error'] ?? null ) ) {
				$detail = (string) ( $decoded[0]['error']['message'] ?? '' );
			}
		}
		$detail = $this->clean( $detail );

		[ $code, $message ] = match ( true ) {
			401 === $status, 403 === $status => array( 'provider_auth', __( 'The provider rejected the API key. Check the key in Connections.', 'mindio-magic-ai' ) ),
			404 === $status => array( 'provider_not_found', __( 'The provider does not recognize this model or address.', 'mindio-magic-ai' ) ),
			429 === $status => array( 'provider_rate_limited', __( 'The provider’s rate limit or quota was reached. Wait a moment and try again.', 'mindio-magic-ai' ) ),
			$status >= 500 => array( 'provider_unavailable', __( 'The provider is temporarily unavailable. Try again shortly.', 'mindio-magic-ai' ) ),
			default => array( 'provider_bad_request', __( 'The provider rejected the request.', 'mindio-magic-ai' ) ),
		};

		return new \WP_Error( $code, '' !== $detail ? $message . ' ' . $detail : $message, array( 'status' => 502, 'provider_status' => $status ) );
	}

	private function clean( string $message ): string {
		$message = wp_strip_all_tags( $message );
		$message = (string) preg_replace( '/(sk|key|AIza)[-_A-Za-z0-9]{8,}/', '[redacted]', $message );
		return mb_substr( trim( $message ), 0, 300 );
	}
}
