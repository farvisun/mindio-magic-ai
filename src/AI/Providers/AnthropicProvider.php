<?php
/**
 * Anthropic Claude through the official PHP SDK.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI\Providers;

use MindioMagicAI\AI\ChatRequest;
use MindioMagicAI\AI\ChatResponse;
use MindioMagicAI\AI\Connection;
use MindioMagicAI\AI\Json;
use MindioMagicAI\AI\ModelCapabilities;
use MindioMagicAI\AI\Presets;
use MindioMagicAI\AI\Provider;
use MindioMagicAI\AI\Transport\DiscoveryStrategy;
use MindioMagicAI\AI\Transport\GuardException;
use MindioMagicAI\AI\Transport\Http;
use MindioMagicAI\AI\Transport\Psr18Client;
use MindioMagicAI\Core\Settings;
use MindioMagicAI\Vendor\Anthropic\Client;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIConnectionException;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIStatusException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AnthropicProvider implements Provider {
	private const FALLBACK_BETA = 'server-side-fallback-2026-07-01';

	/** Content block types that are sent back to the API in later turns. */
	private const REPLAYABLE = array( 'text', 'thinking', 'redacted_thinking', 'tool_use' );

	public function __construct( private Connection $connection ) {}

	public function chat( ChatRequest $request, ?callable $on_event = null ): ChatResponse|\WP_Error {
		$client = $this->client();
		if ( is_wp_error( $client ) ) {
			return $client;
		}

		$streaming    = null !== $on_event;
		$capabilities = ModelCapabilities::resolve( Presets::PROVIDER_ANTHROPIC, $request->model );
		$params       = $this->params( $request, $capabilities, $streaming );
		$fallbacks    = $capabilities['fallbacks'] && $this->connection->is_official_anthropic();

		try {
			if ( ! $streaming ) {
				$message = $fallbacks
					? $client->beta->messages->create( ...$params, fallbacks: 'default', betas: array( self::FALLBACK_BETA ) )
					: $client->messages->create( ...$params );
				return $this->from_message( json_decode( (string) wp_json_encode( $message ), true ) ?: array(), $request->model );
			}

			$stream = $fallbacks
				? $client->beta->messages->createStream( ...$params, fallbacks: 'default', betas: array( self::FALLBACK_BETA ) )
				: $client->messages->createStream( ...$params );

			return $this->consume( $stream, $request->model, $on_event );
		} catch ( GuardException $e ) {
			return $e->error;
		} catch ( APIStatusException $e ) {
			return $this->status_error( $e );
		} catch ( APIConnectionException $e ) {
			return new \WP_Error( 'provider_unavailable', __( 'Could not reach Anthropic. Check the server’s outbound connection and try again.', 'mindio-magic-ai' ), array( 'status' => 502 ) );
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'provider_error', $this->clean( $e->getMessage() ), array( 'status' => 502 ) );
		}
	}

	public function list_models(): array|\WP_Error {
		$client = $this->client();
		if ( is_wp_error( $client ) ) {
			return $client;
		}
		try {
			$ids = array();
			foreach ( $client->models->list( limit: 100 )->getItems() as $model ) {
				$row = json_decode( (string) wp_json_encode( $model ), true );
				if ( ! empty( $row['id'] ) ) {
					$ids[] = (string) $row['id'];
				}
			}
			return $ids;
		} catch ( GuardException $e ) {
			return $e->error;
		} catch ( APIStatusException $e ) {
			return $this->status_error( $e );
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'provider_unavailable', $this->clean( $e->getMessage() ), array( 'status' => 502 ) );
		}
	}

	private function client(): Client|\WP_Error {
		$key = $this->connection->api_key();
		if ( '' === $key ) {
			return new \WP_Error( 'missing_key', __( 'This connection has no API key.', 'mindio-magic-ai' ), array( 'status' => 400 ) );
		}

		DiscoveryStrategy::register();
		$http    = new Http( (array) Settings::get( 'private_hosts' ), $this->connection->timeout() );
		$headers = $this->connection->extra_headers();

		// Every credential and address is explicit, so the SDK never reads ANTHROPIC_* environment variables for them.
		return new Client(
			apiKey: $key,
			authToken: '',
			webhookKey: '',
			baseUrl: $this->connection->base_url,
			requestOptions: array(
				'transporter'          => new Psr18Client( $http, false, $this->connection->timeout() ),
				'streamingTransporter' => new Psr18Client( $http, true, $this->connection->timeout() ),
				'maxRetries'           => 2,
				'timeout'              => $this->connection->timeout(),
				'extraHeaders'         => $headers ? $headers : null,
			),
		);
	}

	/**
	 * @param array{thinking:string,effort:bool} $capabilities
	 * @return array<string,mixed>
	 */
	private function params( ChatRequest $request, array $capabilities, bool $streaming ): array {
		$system = array(
			array(
				'type'          => 'text',
				'text'          => $request->system,
				'cache_control' => array( 'type' => 'ephemeral' ),
			),
		);
		if ( '' !== $request->context ) {
			// Per-request context sits after the cache breakpoint so it never invalidates the cached prefix.
			$system[] = array(
				'type' => 'text',
				'text' => $request->context,
			);
		}

		$params = array(
			'maxTokens' => $request->max_tokens,
			'messages'  => $this->wire_messages( $request->messages ),
			'model'     => $request->model,
			'system'    => $system,
		);

		if ( $request->tools ) {
			$tools = array();
			foreach ( $request->tools as $tool ) {
				$definition = array(
					'name'         => $tool['name'],
					'description'  => $tool['description'],
					'input_schema' => Json::object( $tool['schema'] ),
				);
				if ( $streaming ) {
					$definition['eager_input_streaming'] = true;
				}
				$tools[] = $definition;
			}
			$tools[ count( $tools ) - 1 ]['cache_control'] = array( 'type' => 'ephemeral' );

			$params['tools']      = $tools;
			$params['toolChoice'] = array( 'type' => 'auto' );
		}

		if ( 'adaptive' === $capabilities['thinking'] ) {
			$params['thinking'] = array( 'type' => 'adaptive' );
		}
		if ( $capabilities['effort'] && in_array( $request->effort, Settings::EFFORTS, true ) ) {
			$params['outputConfig'] = array( 'effort' => $request->effort );
		}
		if ( '' !== $request->user_hash ) {
			$params['metadata'] = array( 'userID' => $request->user_hash );
		}

		return $params;
	}

	/**
	 * Convert stored messages to Anthropic wire format.
	 *
	 * @param array<int,array<string,mixed>> $messages
	 * @return array<int,array{role:string,content:array<int,mixed>}>
	 */
	public function wire_messages( array $messages ): array {
		$turns = array();
		foreach ( $messages as $message ) {
			$role   = (string) $message['role'];
			$blocks = array();

			if ( 'assistant' === $role ) {
				$raw = $message['raw'] ?? null;
				if ( Presets::PROVIDER_ANTHROPIC === ( $message['provider'] ?? '' ) && is_array( $raw ) && $raw ) {
					foreach ( $raw as $block ) {
						$type = is_array( $block ) ? (string) ( $block['type'] ?? '' ) : '';
						if ( in_array( $type, self::REPLAYABLE, true ) && ! ( 'text' === $type && '' === (string) ( $block['text'] ?? '' ) ) ) {
							if ( 'tool_use' === $type ) {
								$block['input'] = Json::object( $block['input'] ?? array() );
							}
							$blocks[] = $block;
						}
					}
				} else {
					foreach ( (array) $message['content'] as $block ) {
						if ( 'text' === $block['type'] && '' !== trim( (string) $block['text'] ) ) {
							$blocks[] = array(
								'type' => 'text',
								'text' => (string) $block['text'],
							);
						} elseif ( 'tool_call' === $block['type'] ) {
							$blocks[] = array(
								'type'  => 'tool_use',
								'id'    => (string) $block['id'],
								'name'  => (string) $block['name'],
								'input' => Json::object( $block['input'] ?? array() ),
							);
						}
					}
				}
			} elseif ( 'tool' === $role ) {
				$role = 'user';
				foreach ( (array) $message['content'] as $block ) {
					if ( 'tool_result' === $block['type'] ) {
						$blocks[] = array(
							'type'        => 'tool_result',
							'tool_use_id' => (string) $block['id'],
							'content'     => (string) $block['content'],
							'is_error'    => ! empty( $block['is_error'] ),
						);
					}
				}
			} else {
				$role = 'user';
				foreach ( (array) $message['content'] as $block ) {
					if ( 'text' === $block['type'] && '' !== trim( (string) $block['text'] ) ) {
						$blocks[] = array(
							'type' => 'text',
							'text' => (string) $block['text'],
						);
					} elseif ( 'image' === $block['type'] && ! empty( $block['data'] ) ) {
						$blocks[] = array(
							'type'   => 'image',
							'source' => array(
								'type'       => 'base64',
								'media_type' => (string) $block['media_type'],
								'data'       => (string) $block['data'],
							),
						);
					}
				}
			}

			if ( ! $blocks ) {
				continue;
			}
			$last = count( $turns ) - 1;
			if ( $last >= 0 && $turns[ $last ]['role'] === $role ) {
				$turns[ $last ]['content'] = array_merge( $turns[ $last ]['content'], $blocks );
			} else {
				$turns[] = array(
					'role'    => $role,
					'content' => $blocks,
				);
			}
		}

		// Tool results must lead their user turn, and the conversation must start with the user.
		foreach ( $turns as &$turn ) {
			if ( 'user' === $turn['role'] ) {
				usort( $turn['content'], static fn( $a, $b ): int => ( 'tool_result' === ( $b['type'] ?? '' ) ) <=> ( 'tool_result' === ( $a['type'] ?? '' ) ) );
			}
		}
		unset( $turn );
		while ( $turns && 'user' !== $turns[0]['role'] ) {
			array_shift( $turns );
		}

		return $turns;
	}

	/**
	 * Accumulate a streamed message into a response, forwarding text as it arrives.
	 *
	 * @param iterable<mixed> $stream
	 */
	private function consume( iterable $stream, string $requested_model, callable $on_event ): ChatResponse|\WP_Error {
		$blocks    = array();
		$json      = array();
		$usage     = array( 'input' => 0, 'output' => 0, 'cache_read' => 0, 'cache_write' => 0 );
		$model     = $requested_model;
		$stop      = ChatResponse::STOP_OTHER;
		$category  = '';
		$cancelled = false;
		$finished  = false;

		try {
			foreach ( $stream as $event ) {
				$event = json_decode( (string) wp_json_encode( $event ), true );
				if ( ! is_array( $event ) ) {
					continue;
				}

				switch ( $event['type'] ?? '' ) {
					case 'message_start':
						$model = (string) ( $event['message']['model'] ?? $model );
						$usage = $this->merge_usage( $usage, (array) ( $event['message']['usage'] ?? array() ) );
						break;
					case 'content_block_start':
						$index            = (int) $event['index'];
						$blocks[ $index ] = (array) $event['content_block'];
						if ( 'tool_use' === ( $blocks[ $index ]['type'] ?? '' ) ) {
							$json[ $index ] = '';
							$cancelled      = false === $on_event(
								array(
									'type' => 'tool_start',
									'name' => (string) $blocks[ $index ]['name'],
								)
							);
						}
						break;
					case 'content_block_delta':
						$index = (int) $event['index'];
						$delta = (array) ( $event['delta'] ?? array() );
						switch ( $delta['type'] ?? '' ) {
							case 'text_delta':
								$blocks[ $index ]['text'] = ( $blocks[ $index ]['text'] ?? '' ) . (string) $delta['text'];
								$cancelled                = false === $on_event(
									array(
										'type' => 'text',
										'text' => (string) $delta['text'],
									)
								);
								break;
							case 'input_json_delta':
								$json[ $index ] = ( $json[ $index ] ?? '' ) . (string) ( $delta['partial_json'] ?? '' );
								break;
							case 'thinking_delta':
								$blocks[ $index ]['thinking'] = ( $blocks[ $index ]['thinking'] ?? '' ) . (string) $delta['thinking'];
								break;
							case 'signature_delta':
								$blocks[ $index ]['signature'] = (string) $delta['signature'];
								break;
							case 'citations_delta':
								$blocks[ $index ]['citations'][] = $delta['citation'] ?? null;
								break;
						}
						break;
					case 'content_block_stop':
						$index = (int) $event['index'];
						if ( isset( $json[ $index ] ) ) {
							$parsed = Json::parse_object( $json[ $index ] );
							if ( null === $parsed ) {
								$blocks[ $index ]['input']  = new \stdClass();
								$blocks[ $index ]['_valid'] = false;
							} else {
								$blocks[ $index ]['input'] = Json::object( $parsed );
							}
						}
						break;
					case 'message_delta':
						$stop     = (string) ( $event['delta']['stop_reason'] ?? $stop );
						$category = (string) ( $event['delta']['stop_details']['category'] ?? $category );
						$usage    = $this->merge_usage( $usage, (array) ( $event['usage'] ?? array() ) );
						break;
				}

				if ( $cancelled ) {
					break;
				}
			}
			$finished = true;
		} finally {
			// Stop the transfer now. Left to the destructor, the stream would be read to the end at shutdown.
			if ( ( $cancelled || ! $finished ) && is_object( $stream ) && method_exists( $stream, 'close' ) ) {
				$stream->close();
			}
		}

		ksort( $blocks );
		$message = array(
			'content'     => array_values( $blocks ),
			'stop_reason' => $cancelled ? ChatResponse::STOP_CANCELLED : $stop,
			'stop_details' => array( 'category' => $category ),
			'model'       => $model,
		);

		return $this->build( $message, $usage, $requested_model );
	}

	/** @param array<string,mixed> $message */
	private function from_message( array $message, string $requested_model ): ChatResponse {
		$usage = $this->merge_usage( array( 'input' => 0, 'output' => 0, 'cache_read' => 0, 'cache_write' => 0 ), (array) ( $message['usage'] ?? array() ) );
		return $this->build( $message, $usage, $requested_model );
	}

	/**
	 * @param array<string,mixed>                                        $message
	 * @param array{input:int,output:int,cache_read:int,cache_write:int} $usage
	 */
	private function build( array $message, array $usage, string $requested_model ): ChatResponse {
		$content = array();
		$raw     = array();
		foreach ( (array) ( $message['content'] ?? array() ) as $block ) {
			$type  = (string) ( $block['type'] ?? '' );
			$valid = ! array_key_exists( '_valid', $block ) || false !== $block['_valid'];
			unset( $block['_valid'] );
			if ( 'tool_use' === $type ) {
				$block['input'] = Json::object( $block['input'] ?? array() );
				$content[]      = array(
					'type'  => 'tool_call',
					'id'    => (string) $block['id'],
					'name'  => (string) $block['name'],
					'input' => $block['input'] instanceof \stdClass ? array() : (array) $block['input'],
					'valid' => $valid,
				);
			} elseif ( 'text' === $type ) {
				$content[] = array(
					'type' => 'text',
					'text' => (string) ( $block['text'] ?? '' ),
				);
			}
			$raw[] = $block;
		}

		$stop = match ( (string) ( $message['stop_reason'] ?? '' ) ) {
			'end_turn', 'stop_sequence', 'pause_turn' => ChatResponse::STOP_END,
			'tool_use'   => ChatResponse::STOP_TOOL_USE,
			'max_tokens', 'model_context_window_exceeded' => ChatResponse::STOP_MAX,
			'refusal'    => ChatResponse::STOP_REFUSAL,
			'cancelled'  => ChatResponse::STOP_CANCELLED,
			default      => ChatResponse::STOP_OTHER,
		};

		return new ChatResponse(
			$content,
			$raw,
			$stop,
			$usage,
			(string) ( $message['model'] ?? $requested_model ),
			false,
			(string) ( $message['stop_details']['category'] ?? '' )
		);
	}

	/**
	 * @param array{input:int,output:int,cache_read:int,cache_write:int} $usage
	 * @param array<string,mixed>                                        $wire
	 * @return array{input:int,output:int,cache_read:int,cache_write:int}
	 */
	private function merge_usage( array $usage, array $wire ): array {
		if ( isset( $wire['input_tokens'] ) ) {
			$usage['input'] = max( $usage['input'], (int) $wire['input_tokens'] );
		}
		if ( isset( $wire['output_tokens'] ) ) {
			$usage['output'] = max( $usage['output'], (int) $wire['output_tokens'] );
		}
		if ( isset( $wire['cache_read_input_tokens'] ) ) {
			$usage['cache_read'] = max( $usage['cache_read'], (int) $wire['cache_read_input_tokens'] );
		}
		if ( isset( $wire['cache_creation_input_tokens'] ) ) {
			$usage['cache_write'] = max( $usage['cache_write'], (int) $wire['cache_creation_input_tokens'] );
		}
		return $usage;
	}

	private function status_error( APIStatusException $e ): \WP_Error {
		$status  = (int) $e->status;
		$body    = is_array( $e->body ?? null ) ? $e->body : array();
		$message = $this->clean( (string) ( $body['error']['message'] ?? '' ) );

		[ $code, $fallback ] = match ( true ) {
			401 === $status, 403 === $status => array( 'provider_auth', __( 'Anthropic rejected the API key. Check the key in Connections.', 'mindio-magic-ai' ) ),
			404 === $status => array( 'provider_not_found', __( 'Anthropic does not recognize this model. Choose another model for the connection.', 'mindio-magic-ai' ) ),
			429 === $status => array( 'provider_rate_limited', __( 'Anthropic rate limit reached. Wait a moment and try again.', 'mindio-magic-ai' ) ),
			$status >= 500 => array( 'provider_unavailable', __( 'Anthropic is temporarily unavailable. Try again shortly.', 'mindio-magic-ai' ) ),
			default => array( 'provider_bad_request', __( 'Anthropic rejected the request.', 'mindio-magic-ai' ) ),
		};

		return new \WP_Error( $code, '' !== $message ? $fallback . ' ' . $message : $fallback, array( 'status' => 502, 'provider_status' => $status ) );
	}

	private function clean( string $message ): string {
		$message = wp_strip_all_tags( $message );
		$message = (string) preg_replace( '/sk-[A-Za-z0-9_-]{8,}/', '[redacted]', $message );
		return mb_substr( trim( $message ), 0, 300 );
	}
}
