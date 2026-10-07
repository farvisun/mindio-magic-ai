<?php
/**
 * Assistant runs: one user message and the bounded model/tool loop that answers it.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Assistant;

use MindioMagicAI\Agent\ProposalQueue;
use MindioMagicAI\Agent\RunEmitter;
use MindioMagicAI\Agent\RunEvents;
use MindioMagicAI\Agent\SchemaValidator;
use MindioMagicAI\Agent\Tool;
use MindioMagicAI\Agent\ToolRegistry;
use MindioMagicAI\AI\ChatRequest;
use MindioMagicAI\AI\ChatResponse;
use MindioMagicAI\AI\Connections;
use MindioMagicAI\AI\Gateway;
use MindioMagicAI\AI\Json;
use MindioMagicAI\AI\ModelCapabilities;
use MindioMagicAI\Core\Access;
use MindioMagicAI\Core\AuditLog;
use MindioMagicAI\Core\Installer;
use MindioMagicAI\Core\Pricing;
use MindioMagicAI\Core\Settings;
use MindioMagicAI\Core\Time;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Runs {
	public const QUEUED    = 'queued';
	public const RUNNING   = 'running';
	public const STOPPING  = 'stopping';
	public const COMPLETED = 'completed';
	public const STOPPED   = 'stopped';
	public const FAILED    = 'failed';

	private const MAX_IMAGES      = 4;
	private const MAX_IMAGE_BYTES = 3932160;
	private const IMAGE_TYPES     = array( 'image/png', 'image/jpeg', 'image/webp', 'image/gif' );
	private const RESULT_LIMIT    = 20000;
	public const USER_AUTO_APPLY  = 'mindio_magic_ai_auto_apply';

	private Conversations $conversations;

	public function __construct() {
		$this->conversations = new Conversations();
	}

	/**
	 * Store the user's message and queue a run for it.
	 *
	 * @param array<int,array{name?:string,media_type?:string,data?:string}> $images
	 * @param array<string,mixed>                                          $screen
	 * @return array<string,mixed>|\WP_Error
	 */
	public function create( int $conversation_id, int $user_id, string $text, array $images, array $screen ): array|\WP_Error {
		$conversation = $this->conversations->get( $conversation_id );
		if ( ! $conversation || $conversation['user_id'] !== $user_id ) {
			return new \WP_Error( 'not_found', __( 'Conversation not found.', 'mindio-magic-ai' ), array( 'status' => 404 ) );
		}
		if ( $this->active_run( $conversation_id ) ) {
			return new \WP_Error( 'run_active', __( 'The assistant is still answering. Wait for it to finish or stop it.', 'mindio-magic-ai' ), array( 'status' => 409 ) );
		}

		$text = trim( wp_check_invalid_utf8( $text ) );
		if ( '' === $text && ! $images ) {
			return new \WP_Error( 'empty_message', __( 'Write a message first.', 'mindio-magic-ai' ), array( 'status' => 400 ) );
		}
		if ( mb_strlen( $text ) > 50000 ) {
			return new \WP_Error( 'message_too_long', __( 'The message is too long.', 'mindio-magic-ai' ), array( 'status' => 400 ) );
		}

		$content = array();
		$note    = ProposalQueue::take_notes( $conversation_id );
		if ( '' !== $note ) {
			$content[] = array(
				'type' => 'note',
				'text' => $note,
			);
		}
		$content[] = array(
			'type' => 'text',
			'text' => $text,
		);

		foreach ( array_slice( $images, 0, self::MAX_IMAGES ) as $image ) {
			$media = (string) ( $image['media_type'] ?? '' );
			$data  = (string) ( $image['data'] ?? '' );
			$bytes = base64_decode( $data, true );
			if ( ! in_array( $media, self::IMAGE_TYPES, true ) || false === $bytes || strlen( $bytes ) > self::MAX_IMAGE_BYTES ) {
				return new \WP_Error( 'invalid_image', __( 'Images must be PNG, JPEG, WebP, or GIF and smaller than 3.75 MB.', 'mindio-magic-ai' ), array( 'status' => 400 ) );
			}
			$info = @getimagesizefromstring( $bytes ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Invalid images return false.
			if ( ! is_array( $info ) || ( $info['mime'] ?? '' ) !== $media ) {
				return new \WP_Error( 'invalid_image', __( 'An attached file is not a valid image.', 'mindio-magic-ai' ), array( 'status' => 400 ) );
			}
			$content[] = array(
				'type'       => 'image',
				'media_type' => $media,
				'data'       => base64_encode( $bytes ),
				'name'       => sanitize_file_name( (string) ( $image['name'] ?? '' ) ),
			);
		}

		if ( '' === $conversation['title'] ) {
			$this->conversations->update( $conversation_id, array( 'title' => wp_html_excerpt( $text, 60, '…' ) ) );
		}

		global $wpdb;
		$now = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned table.
		$wpdb->insert(
			Installer::table( 'runs' ),
			array(
				'conversation_id' => $conversation_id,
				'user_id'         => $user_id,
				'feature'         => 'assistant',
				'status'          => self::QUEUED,
				'context'         => Json::encode( $this->screen( $screen ) ),
				'created_at'      => $now,
				'heartbeat_at'    => $now,
			)
		);
		$run_id = (int) $wpdb->insert_id;
		$this->conversations->add_message( $conversation_id, $run_id, 'user', $content );

		return (array) $this->get( $run_id );
	}

	/** @return array<string,mixed>|null */
	public function get( int $run_id ): ?array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Live run state.
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', Installer::table( 'runs' ), $run_id ), ARRAY_A );
		if ( ! is_array( $row ) ) {
			return null;
		}
		return array(
			'id'              => (int) $row['id'],
			'conversation_id' => (int) $row['conversation_id'],
			'user_id'         => (int) $row['user_id'],
			'status'          => (string) $row['status'],
			'steps'           => (int) $row['steps'],
			'input_tokens'    => (int) $row['input_tokens'],
			'output_tokens'   => (int) $row['output_tokens'],
			'cost_micros'     => (int) $row['cost_micros'],
			'tainted'         => (bool) $row['tainted'],
			'error_code'      => (string) $row['error_code'],
			'error_message'   => (string) $row['error_message'],
			'created_at'      => Time::iso( (string) $row['created_at'] ),
			'finished_at'     => Time::iso( $row['finished_at'] ),
		);
	}

	public function stop( int $run_id ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic transition.
		$updated = $wpdb->query( $wpdb->prepare( 'UPDATE %i SET status = %s WHERE id = %d AND status IN (%s, %s)', Installer::table( 'runs' ), self::STOPPING, $run_id, self::QUEUED, self::RUNNING ) );
		return (bool) $updated;
	}

	public function active_run( int $conversation_id ): int {
		global $wpdb;
		$stale = gmdate( 'Y-m-d H:i:s', time() - 3 * MINUTE_IN_SECONDS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Live run state.
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM %i WHERE conversation_id = %d AND status IN ('queued','running','stopping') AND heartbeat_at >= %s ORDER BY id DESC LIMIT 1", Installer::table( 'runs' ), $conversation_id, $stale ) );
	}

	/**
	 * Run the model/tool loop. Only one worker can claim a queued run.
	 *
	 * @param callable(int,string,array<string,mixed>):void|null $writer Streams stored events to the open response.
	 */
	public function execute( int $run_id, ?callable $writer = null ): void {
		global $wpdb;
		$table = Installer::table( 'runs' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic claim.
		$claimed = $wpdb->query( $wpdb->prepare( 'UPDATE %i SET status = %s, heartbeat_at = %s WHERE id = %d AND status = %s', $table, self::RUNNING, current_time( 'mysql', true ), $run_id, self::QUEUED ) );
		if ( 1 !== (int) $claimed ) {
			return;
		}

		$run          = (array) $this->get( $run_id );
		$emitter      = new RunEmitter( $run_id, $writer );
		$conversation = $this->conversations->get( (int) $run['conversation_id'] );
		$settings     = Settings::all();
		$started      = microtime( true );
		$deadline     = $started + (int) $settings['max_wall_seconds'];
		$totals       = array( 'input' => 0, 'output' => 0, 'cost' => 0 );
		$tainted      = false;
		$status       = self::COMPLETED;
		$error        = null;
		$last_check   = 0.0;

		register_shutdown_function(
			function () use ( $run_id ): void {
				$current = $this->get( $run_id );
				if ( $current && in_array( $current['status'], array( self::RUNNING, self::STOPPING ), true ) ) {
					$this->finish( $run_id, self::FAILED, 'interrupted', __( 'The server stopped this run before it finished.', 'mindio-magic-ai' ) );
					RunEvents::append( $run_id, 'done', array( 'status' => self::FAILED ) );
				}
			}
		);

		$connections = new Connections();
		$connection  = $conversation ? $connections->get( (int) $conversation['connection_id'] ) : null;
		if ( ! $conversation || ! $connection || ! $connection->has_key() ) {
			$emitter->emit( 'error', array( 'code' => 'no_connection', 'message' => __( 'The connection this conversation uses is missing or has no key. Start a new conversation or fix it in Connections.', 'mindio-magic-ai' ) ) );
			$this->finish( $run_id, self::FAILED, 'no_connection', '' );
			$emitter->emit( 'done', array( 'status' => self::FAILED ) );
			return;
		}

		$model        = '' !== $conversation['model'] ? $conversation['model'] : $connection->default_model();
		$capabilities = ModelCapabilities::resolve( $connection->provider, $model );
		$registry     = ToolRegistry::instance();
		$tools        = $this->visible_tools( $registry );
		$definitions  = $capabilities['tools'] ? array_map( static fn( Tool $tool ): array => $tool->definition(), $tools ) : array();
		$context      = Prompt::context( (array) json_decode( (string) $wpdb->get_var( $wpdb->prepare( 'SELECT context FROM %i WHERE id = %d', $table, $run_id ) ), true ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Run row.
		$max_steps    = (int) $settings['max_steps'];
		$max_tokens   = min( (int) $settings['max_output_tokens'], $capabilities['max_output'] );
		$user_hash    = substr( hash_hmac( 'sha256', (string) get_current_user_id(), wp_salt( 'nonce' ) ), 0, 32 );
		$auto_apply   = ! empty( $settings['allow_auto_apply'] ) && (bool) get_user_meta( get_current_user_id(), self::USER_AUTO_APPLY, true );

		$emitter->emit(
			'start',
			array(
				'run_id' => $run_id,
				'model'  => $model,
			)
		);

		$should_stop = function () use ( $run_id, &$last_check, $deadline ): bool {
			if ( microtime( true ) > $deadline ) {
				return true;
			}
			if ( microtime( true ) - $last_check < 0.5 ) {
				return false;
			}
			$last_check = microtime( true );
			$this->heartbeat( $run_id );
			$current = $this->get( $run_id );
			return ! $current || self::STOPPING === $current['status'];
		};

		for ( $step = 1; $step <= $max_steps; $step++ ) {
			if ( $should_stop() ) {
				$status = microtime( true ) > $deadline ? self::COMPLETED : self::STOPPED;
				if ( self::COMPLETED === $status ) {
					$emitter->emit( 'notice', array( 'code' => 'time_limit', 'message' => __( 'The run reached its time limit.', 'mindio-magic-ai' ) ) );
				}
				break;
			}

			$request = new ChatRequest(
				model: $model,
				system: Prompt::system(),
				messages: $this->history( (int) $conversation['id'] ),
				tools: $definitions,
				max_tokens: $max_tokens,
				effort: (string) $settings['effort'],
				context: $context,
				user_hash: $user_hash,
			);

			$response = Gateway::chat(
				$connection,
				$request,
				'assistant',
				$run_id,
				function ( array $event ) use ( $emitter, $should_stop ): bool {
					if ( 'text' === $event['type'] ) {
						$emitter->text( (string) $event['text'] );
					} elseif ( 'tool_start' === $event['type'] ) {
						$emitter->emit( 'tool_start', array( 'name' => (string) $event['name'] ) );
					}
					return ! $should_stop();
				}
			);
			$emitter->flush();

			if ( is_wp_error( $response ) ) {
				$error  = $response;
				$status = self::FAILED;
				$emitter->emit(
					'error',
					array(
						'code'    => $response->get_error_code(),
						'message' => $response->get_error_message(),
					)
				);
				break;
			}

			$totals['input']  += $response->usage['input'] + $response->usage['cache_read'] + $response->usage['cache_write'];
			$totals['output'] += $response->usage['output'];
			$totals['cost']   += (int) Pricing::cost_micros( $response->model, $response->usage );
			$this->update_totals( $run_id, $step, $totals, $tainted );

			$emitter->emit(
				'usage',
				array(
					'input_tokens'  => $totals['input'],
					'output_tokens' => $totals['output'],
					'cost_micros'   => $totals['cost'],
				)
			);

			if ( ChatResponse::STOP_REFUSAL === $response->stop_reason ) {
				$this->conversations->add_message(
					(int) $conversation['id'],
					$run_id,
					'assistant',
					array(
						array(
							'type' => 'notice',
							'code' => 'refusal',
						),
					),
					null,
					$connection->provider,
					$response->model
				);
				$emitter->emit( 'notice', array( 'code' => 'refusal', 'message' => __( 'The model declined to answer this request.', 'mindio-magic-ai' ) ) );
				break;
			}

			$this->conversations->add_message( (int) $conversation['id'], $run_id, 'assistant', $response->content, $response->raw, $connection->provider, $response->model );

			$calls     = $response->tool_calls();
			$cancelled = ChatResponse::STOP_CANCELLED === $response->stop_reason;
			if ( $calls ) {
				$results = array();
				foreach ( $calls as $call ) {
					if ( $cancelled || $should_stop() ) {
						$cancelled = true;
						$results[] = $this->result_block( $call, 'Not run: the user stopped the run.', true );
						continue;
					}
					$results[] = $this->call_tool( $registry, $call, $run_id, (int) $conversation['id'], $emitter, $tainted, $auto_apply );
				}
				$this->conversations->add_message( (int) $conversation['id'], $run_id, 'tool', $results );
				$this->update_totals( $run_id, $step, $totals, $tainted );
			}

			if ( $cancelled ) {
				$status = self::STOPPED;
				break;
			}
			if ( ChatResponse::STOP_MAX === $response->stop_reason ) {
				$emitter->emit( 'notice', array( 'code' => 'max_tokens', 'message' => __( 'The answer reached the output limit and may be cut off.', 'mindio-magic-ai' ) ) );
				break;
			}
			if ( ! $calls ) {
				break;
			}
			if ( $step === $max_steps ) {
				$emitter->emit( 'notice', array( 'code' => 'step_limit', 'message' => __( 'The assistant reached its step limit for one message. Send another message to continue.', 'mindio-magic-ai' ) ) );
			}
		}

		$this->finish( $run_id, $status, $error ? $error->get_error_code() : '', $error ? $error->get_error_message() : '' );
		AuditLog::write(
			'run.' . $status,
			'conversation',
			(string) $conversation['id'],
			array(
				'run'     => $run_id,
				'model'   => $model,
				'tokens'  => $totals['input'] + $totals['output'],
				'tainted' => $tainted,
				'error'   => $error ? $error->get_error_code() : '',
			),
			self::FAILED !== $status
		);
		$emitter->emit(
			'done',
			array(
				'status'        => $status,
				'input_tokens'  => $totals['input'],
				'output_tokens' => $totals['output'],
				'cost_micros'   => $totals['cost'],
			)
		);
	}

	/**
	 * @param array<string,mixed> $call
	 * @return array<string,mixed>
	 */
	private function call_tool( ToolRegistry $registry, array $call, int $run_id, int $conversation_id, RunEmitter $emitter, bool &$tainted, bool $auto_apply ): array {
		$tool = $registry->get( (string) $call['name'] );
		if ( ! $tool || ! $this->tool_visible( $tool ) ) {
			return $this->tool_error( $emitter, $call, 'Error: unknown tool.' );
		}
		if ( empty( $call['valid'] ) && array_key_exists( 'valid', $call ) ) {
			return $this->tool_error( $emitter, $call, 'Error INVALID_JSON: the arguments were not valid JSON. Call the tool again with a valid JSON object.' );
		}
		$args  = (array) $call['input'];
		$valid = ( new SchemaValidator() )->validate( $args, $tool->schema );
		if ( is_wp_error( $valid ) ) {
			return $this->tool_error( $emitter, $call, 'Error: invalid arguments. ' . $valid->get_error_message() );
		}
		$permitted = $tool->permitted( $args );
		if ( true !== $permitted ) {
			$message = is_wp_error( $permitted ) ? $permitted->get_error_message() : __( 'You are not allowed to do this.', 'mindio-magic-ai' );
			return $this->tool_error( $emitter, $call, 'Error: ' . $message );
		}

		if ( ! $tool->is_write() ) {
			$emitter->emit( 'tool_run', array( 'name' => $tool->name, 'label' => $tool->label ) );
			try {
				$result = $tool->run( $args );
			} catch ( \Throwable $e ) {
				$result = new \WP_Error( 'tool_failed', wp_strip_all_tags( $e->getMessage() ) );
			}
			if ( is_wp_error( $result ) ) {
				return $this->tool_error( $emitter, $call, 'Error: ' . $result->get_error_message() );
			}
			$untrusted = $tool->untrusted || ! empty( $result['_untrusted'] );
			unset( $result['_untrusted'] );
			$json = Json::encode( $result );
			if ( mb_strlen( $json ) > self::RESULT_LIMIT ) {
				$json = mb_substr( $json, 0, self::RESULT_LIMIT ) . '…[truncated]';
			}
			if ( $untrusted ) {
				$tainted = true;
				$json    = '<untrusted_data source="' . $tool->name . '">' . str_replace( '</untrusted_data>', '', $json ) . '</untrusted_data>';
			}
			$emitter->emit( 'tool_result', array( 'name' => $tool->name, 'ok' => true ) );
			return $this->result_block( $call, $json, false );
		}

		$preview = $tool->preview( $args );
		if ( is_wp_error( $preview ) ) {
			return $this->tool_error( $emitter, $call, 'Error: ' . $preview->get_error_message() );
		}

		$proposal_id = ProposalQueue::create( $run_id, $conversation_id, get_current_user_id(), $tool, $args, $preview );

		if ( $auto_apply && $tool->auto_apply && ! $tainted ) {
			$applied = ProposalQueue::apply( $proposal_id, get_current_user_id(), true );
			if ( ! is_wp_error( $applied ) ) {
				$emitter->emit( 'proposal', $applied );
				$block             = $this->result_block( $call, 'Applied automatically (the user allows drafts to be created without review): ' . Json::encode( $applied['result']['target'] ?? array() ), false );
				$block['proposal'] = $proposal_id;
				return $block;
			}
		}

		$emitter->emit( 'proposal', ProposalQueue::to_public( (array) ProposalQueue::get( $proposal_id ) ) );
		$block             = $this->result_block( $call, sprintf( 'Proposal %s is waiting for the user to approve it in the chat. The change has NOT been made yet. Briefly tell the user what you proposed; do not repeat the full content.', $proposal_id ), false );
		$block['proposal'] = $proposal_id;
		return $block;
	}

	/**
	 * A failed call: the activity chip shows the error, and the model reads why.
	 *
	 * @param array<string,mixed> $call
	 * @return array<string,mixed>
	 */
	private function tool_error( RunEmitter $emitter, array $call, string $content ): array {
		$emitter->emit( 'tool_result', array( 'name' => (string) $call['name'], 'ok' => false, 'message' => $content ) );
		return $this->result_block( $call, $content, true );
	}

	/**
	 * @param array<string,mixed> $call
	 * @return array<string,mixed>
	 */
	private function result_block( array $call, string $content, bool $is_error ): array {
		return array(
			'type'     => 'tool_result',
			'id'       => (string) $call['id'],
			'name'     => (string) $call['name'],
			'content'  => $content,
			'is_error' => $is_error,
		);
	}

	/**
	 * Conversation history for the model, with notes turned into text and tool results paired with their calls.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function history( int $conversation_id ): array {
		$messages = $this->conversations->messages( $conversation_id );
		$open     = array();
		$out      = array();
		foreach ( $messages as $message ) {
			$content = array();
			foreach ( $message['content'] as $block ) {
				if ( 'note' === ( $block['type'] ?? '' ) ) {
					$content[] = array(
						'type' => 'text',
						'text' => (string) $block['text'],
					);
				} else {
					$content[] = $block;
				}
			}
			$message['content'] = $content;

			if ( 'assistant' === $message['role'] ) {
				if ( $open ) {
					$out[] = $this->missing_results( $open );
				}
				$open = array();
				foreach ( $content as $block ) {
					if ( 'tool_call' === ( $block['type'] ?? '' ) ) {
						$open[ (string) $block['id'] ] = (string) $block['name'];
					}
				}
			} elseif ( 'tool' === $message['role'] ) {
				foreach ( $content as $block ) {
					unset( $open[ (string) ( $block['id'] ?? '' ) ] );
				}
			} elseif ( $open ) {
				$out[] = $this->missing_results( $open );
				$open  = array();
			}
			$out[] = $message;
		}
		if ( $open ) {
			$out[] = $this->missing_results( $open );
		}
		return $out;
	}

	/**
	 * @param array<string,string> $open Tool call IDs to names.
	 * @return array<string,mixed>
	 */
	private function missing_results( array $open ): array {
		$blocks = array();
		foreach ( $open as $id => $name ) {
			$blocks[] = array(
				'type'     => 'tool_result',
				'id'       => $id,
				'name'     => $name,
				'content'  => 'Not run: the run ended before this tool was called.',
				'is_error' => true,
			);
		}
		return array(
			'role'     => 'tool',
			'content'  => $blocks,
			'raw'      => null,
			'provider' => '',
		);
	}

	/** @return array<int,Tool> */
	private function visible_tools( ToolRegistry $registry ): array {
		return array_values( array_filter( $registry->all(), array( $this, 'tool_visible' ) ) );
	}

	private function tool_visible( Tool $tool ): bool {
		if ( ! current_user_can( Access::USE_ASSISTANT ) ) {
			return false;
		}
		return match ( $tool->group ) {
			'woocommerce' => current_user_can( 'edit_products' ) || current_user_can( 'view_woocommerce_reports' ),
			'comments'    => current_user_can( 'moderate_comments' ),
			default       => current_user_can( 'edit_posts' ),
		};
	}

	/**
	 * @param array<string,mixed> $screen
	 * @return array<string,mixed>
	 */
	private function screen( array $screen ): array {
		return array(
			'screen'    => sanitize_key( (string) ( $screen['screen'] ?? '' ) ),
			'post_id'   => absint( $screen['post_id'] ?? 0 ),
			'post_type' => sanitize_key( (string) ( $screen['post_type'] ?? '' ) ),
		);
	}

	private function heartbeat( int $run_id ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Run row.
		$wpdb->update( Installer::table( 'runs' ), array( 'heartbeat_at' => current_time( 'mysql', true ) ), array( 'id' => $run_id ) );
	}

	/** @param array{input:int,output:int,cost:int} $totals */
	private function update_totals( int $run_id, int $steps, array $totals, bool $tainted ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Run row.
		$wpdb->update(
			Installer::table( 'runs' ),
			array(
				'steps'         => $steps,
				'input_tokens'  => $totals['input'],
				'output_tokens' => $totals['output'],
				'cost_micros'   => $totals['cost'],
				'tainted'       => $tainted ? 1 : 0,
				'heartbeat_at'  => current_time( 'mysql', true ),
			),
			array( 'id' => $run_id )
		);
	}

	private function finish( int $run_id, string $status, string $code, string $message ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Run row.
		$wpdb->update(
			Installer::table( 'runs' ),
			array(
				'status'        => $status,
				'error_code'    => $code,
				'error_message' => $message,
				'finished_at'   => current_time( 'mysql', true ),
			),
			array( 'id' => $run_id )
		);
	}
}
