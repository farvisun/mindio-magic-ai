<?php
/**
 * Assistant conversations and their append-only message history.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Assistant;

use MindioMagicAI\AI\Json;
use MindioMagicAI\Core\Installer;
use MindioMagicAI\Core\Time;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Conversations {
	public function create( int $user_id, int $connection_id, string $model, string $title = '', int $forked_from = 0 ): int {
		global $wpdb;
		$now = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned table.
		$wpdb->insert(
			Installer::table( 'conversations' ),
			array(
				'user_id'       => $user_id,
				'title'         => mb_substr( sanitize_text_field( $title ), 0, 190 ),
				'connection_id' => $connection_id,
				'model'         => $model,
				'forked_from'   => $forked_from,
				'created_at'    => $now,
				'updated_at'    => $now,
			)
		);
		return (int) $wpdb->insert_id;
	}

	/** @return array<string,mixed>|null */
	public function get( int $id ): ?array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', Installer::table( 'conversations' ), $id ), ARRAY_A );
		return is_array( $row ) ? $this->shape( $row ) : null;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function list( int $user_id, string $search = '', bool $archived = false, int $limit = 50 ): array {
		global $wpdb;
		$table = Installer::table( 'conversations' );
		if ( '' !== $search ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT c.* FROM %i c WHERE c.user_id = %d AND c.archived = %d AND ( c.title LIKE %s OR EXISTS ( SELECT 1 FROM %i m WHERE m.conversation_id = c.id AND m.role = %s AND m.content LIKE %s ) ) ORDER BY c.pinned DESC, c.updated_at DESC LIMIT %d',
					$table,
					$user_id,
					$archived ? 1 : 0,
					'%' . $wpdb->esc_like( $search ) . '%',
					Installer::table( 'messages' ),
					'user',
					'%' . $wpdb->esc_like( $search ) . '%',
					max( 1, min( 200, $limit ) )
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE user_id = %d AND archived = %d ORDER BY pinned DESC, updated_at DESC LIMIT %d',
					$table,
					$user_id,
					$archived ? 1 : 0,
					max( 1, min( 200, $limit ) )
				),
				ARRAY_A
			);
		}
		return array_map( array( $this, 'shape' ), (array) $rows );
	}

	/**
	 * @param array<string,mixed> $fields
	 */
	public function update( int $id, array $fields ): void {
		$changes = array();
		if ( array_key_exists( 'title', $fields ) ) {
			$changes['title'] = mb_substr( sanitize_text_field( (string) $fields['title'] ), 0, 190 );
		}
		if ( array_key_exists( 'pinned', $fields ) ) {
			$changes['pinned'] = rest_sanitize_boolean( $fields['pinned'] ) ? 1 : 0;
		}
		if ( array_key_exists( 'archived', $fields ) ) {
			$changes['archived'] = rest_sanitize_boolean( $fields['archived'] ) ? 1 : 0;
		}
		if ( ! $changes ) {
			return;
		}
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
		$wpdb->update( Installer::table( 'conversations' ), $changes, array( 'id' => $id ) );
	}

	public function touch( int $id ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
		$wpdb->update( Installer::table( 'conversations' ), array( 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $id ) );
	}

	public function delete( int $id ): void {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned tables.
		$runs = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM %i WHERE conversation_id = %d', Installer::table( 'runs' ), $id ) );
		foreach ( (array) $runs as $run_id ) {
			$wpdb->delete( Installer::table( 'run_events' ), array( 'run_id' => (int) $run_id ), array( '%d' ) );
		}
		$wpdb->delete( Installer::table( 'runs' ), array( 'conversation_id' => $id ), array( '%d' ) );
		$wpdb->delete( Installer::table( 'messages' ), array( 'conversation_id' => $id ), array( '%d' ) );
		$wpdb->query( $wpdb->prepare( "UPDATE %i SET status = 'expired' WHERE conversation_id = %d AND status = 'pending'", Installer::table( 'proposals' ), $id ) );
		$wpdb->delete( Installer::table( 'conversations' ), array( 'id' => $id ), array( '%d' ) );
		// phpcs:enable
	}

	/**
	 * @param array<int,array<string,mixed>> $content
	 */
	public function add_message( int $conversation_id, int $run_id, string $role, array $content, mixed $raw = null, string $provider = '', string $model = '' ): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned table.
		$wpdb->insert(
			Installer::table( 'messages' ),
			array(
				'conversation_id' => $conversation_id,
				'run_id'          => $run_id,
				'role'            => $role,
				'content'         => Json::encode( $content ),
				'raw'             => null === $raw ? null : Json::encode( $raw ),
				'provider'        => $provider,
				'model'           => $model,
				'created_at'      => current_time( 'mysql', true ),
			)
		);
		$this->touch( $conversation_id );
		return (int) $wpdb->insert_id;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function messages( int $conversation_id, int $before_id = 0 ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE conversation_id = %d AND ( %d = 0 OR id < %d ) ORDER BY id ASC',
				Installer::table( 'messages' ),
				$conversation_id,
				$before_id,
				$before_id
			),
			ARRAY_A
		);
		return array_map(
			static function ( array $row ): array {
				$content = Json::decode( (string) $row['content'] );
				return array(
					'id'         => (int) $row['id'],
					'run_id'     => (int) $row['run_id'],
					'role'       => (string) $row['role'],
					'content'    => is_array( $content ) ? $content : array(),
					'raw'        => null === $row['raw'] ? null : Json::decode( (string) $row['raw'] ),
					'provider'   => (string) $row['provider'],
					'model'      => (string) $row['model'],
					'created_at' => Time::iso( (string) $row['created_at'] ),
				);
			},
			(array) $rows
		);
	}

	/**
	 * Copy the history before a message into a new conversation, so editing an earlier turn never rewrites the original.
	 */
	public function fork( int $id, int $user_id, int $before_message_id ): int {
		$source = $this->get( $id );
		if ( ! $source ) {
			return 0;
		}
		$new_id = $this->create( $user_id, (int) $source['connection_id'], (string) $source['model'], (string) $source['title'], $id );
		foreach ( $this->messages( $id, $before_message_id ) as $message ) {
			$this->add_message( $new_id, 0, $message['role'], $message['content'], $message['raw'], $message['provider'], $message['model'] );
		}
		return $new_id;
	}

	/**
	 * Messages shaped for the chat UI. Images are replaced by a placeholder and tool payloads trimmed.
	 *
	 * @param array<int,array<string,mixed>> $messages
	 * @return array<int,array<string,mixed>>
	 */
	public function to_public( array $messages ): array {
		$out = array();
		foreach ( $messages as $message ) {
			$blocks = array();
			foreach ( $message['content'] as $block ) {
				$type = (string) ( $block['type'] ?? '' );
				if ( 'image' === $type ) {
					$blocks[] = array(
						'type'       => 'image',
						'media_type' => (string) $block['media_type'],
						'name'       => (string) ( $block['name'] ?? '' ),
					);
				} elseif ( 'tool_result' === $type ) {
					$blocks[] = array(
						'type'     => 'tool_result',
						'id'       => (string) $block['id'],
						'name'     => (string) ( $block['name'] ?? '' ),
						'is_error' => ! empty( $block['is_error'] ),
						'proposal' => (string) ( $block['proposal'] ?? '' ),
						'summary'  => (string) ( $block['summary'] ?? '' ),
					);
				} elseif ( 'tool_call' === $type ) {
					$blocks[] = array(
						'type' => 'tool_call',
						'id'   => (string) $block['id'],
						'name' => (string) $block['name'],
					);
				} elseif ( 'note' === $type ) {
					continue;
				} else {
					$blocks[] = $block;
				}
			}
			$out[] = array(
				'id'         => $message['id'],
				'run_id'     => $message['run_id'],
				'role'       => $message['role'],
				'content'    => $blocks,
				'model'      => $message['model'],
				'created_at' => $message['created_at'],
			);
		}
		return $out;
	}

	/**
	 * @param array<string,mixed> $row
	 * @return array<string,mixed>
	 */
	private function shape( array $row ): array {
		return array(
			'id'            => (int) $row['id'],
			'user_id'       => (int) $row['user_id'],
			'title'         => (string) $row['title'],
			'connection_id' => (int) $row['connection_id'],
			'model'         => (string) $row['model'],
			'pinned'        => (bool) $row['pinned'],
			'archived'      => (bool) $row['archived'],
			'forked_from'   => (int) $row['forked_from'],
			'created_at'    => Time::iso( (string) $row['created_at'] ),
			'updated_at'    => Time::iso( (string) $row['updated_at'] ),
		);
	}
}
