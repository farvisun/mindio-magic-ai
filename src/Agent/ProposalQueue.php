<?php
/**
 * Changes the model proposed, waiting for the user to apply or reject them.
 *
 * Arguments stay on the server: the browser only ever sends a proposal ID.
 * Applying re-checks the user's capability and that the target has not
 * changed since the proposal was made, claims the row atomically so a
 * double click cannot apply twice, and journals the change for undo.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Agent;

use MindioMagicAI\AI\Json;
use MindioMagicAI\Core\AuditLog;
use MindioMagicAI\Core\Installer;
use MindioMagicAI\Core\Settings;
use MindioMagicAI\Core\Time;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ProposalQueue {
	public const PENDING  = 'pending';
	public const APPLYING = 'applying';
	public const APPLIED  = 'applied';
	public const REJECTED = 'rejected';
	public const FAILED   = 'failed';
	public const EXPIRED  = 'expired';
	public const STALE    = 'stale';
	public const REVERTED = 'reverted';

	/**
	 * @param array<string,mixed> $args
	 * @param array<string,mixed> $preview
	 */
	public static function create( int $run_id, int $conversation_id, int $user_id, Tool $tool, array $args, array $preview ): string {
		global $wpdb;

		$public_id = 'pr_' . bin2hex( random_bytes( 12 ) );
		$encoded   = Json::encode( $args );
		$ttl       = (int) Settings::get( 'proposal_ttl_minutes' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned table.
		$wpdb->insert(
			Installer::table( 'proposals' ),
			array(
				'public_id'       => $public_id,
				'run_id'          => $run_id,
				'conversation_id' => $conversation_id,
				'user_id'         => $user_id,
				'tool'            => $tool->name,
				'arguments'       => $encoded,
				'args_hash'       => hash( 'sha256', $tool->name . "\0" . $encoded ),
				'fingerprint'     => $tool->fingerprint( $args ),
				'preview'         => Json::encode( $preview ),
				'status'          => self::PENDING,
				'created_at'      => current_time( 'mysql', true ),
				'expires_at'      => gmdate( 'Y-m-d H:i:s', time() + max( 5, $ttl ) * MINUTE_IN_SECONDS ),
			)
		);

		return $public_id;
	}

	/** @return array<string,mixed>|null */
	public static function get( string $public_id ): ?array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Live state.
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE public_id = %s', Installer::table( 'proposals' ), $public_id ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function for_conversation( int $conversation_id ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Live state.
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE conversation_id = %d ORDER BY id ASC', Installer::table( 'proposals' ), $conversation_id ), ARRAY_A );
		return array_map( array( self::class, 'to_public' ), (array) $rows );
	}

	/**
	 * @return array<string,mixed>|\WP_Error The applied proposal.
	 */
	public static function apply( string $public_id, int $user_id, bool $automatic = false ): array|\WP_Error {
		$row = self::get( $public_id );
		if ( ! $row || (int) $row['user_id'] !== $user_id ) {
			return new \WP_Error( 'not_found', __( 'Proposal not found.', 'mindio-magic-ai' ), array( 'status' => 404 ) );
		}
		if ( self::PENDING !== $row['status'] ) {
			return new \WP_Error( 'not_pending', __( 'This proposal was already handled.', 'mindio-magic-ai' ), array( 'status' => 409 ) );
		}
		if ( strtotime( $row['expires_at'] . ' UTC' ) < time() ) {
			self::set_status( (int) $row['id'], self::EXPIRED );
			return new \WP_Error( 'expired', __( 'This proposal expired. Ask the assistant to propose it again.', 'mindio-magic-ai' ), array( 'status' => 410 ) );
		}

		$tool = ToolRegistry::instance()->get( (string) $row['tool'] );
		$args = json_decode( (string) $row['arguments'], true );
		if ( ! $tool || ! is_array( $args ) || ! hash_equals( (string) $row['args_hash'], hash( 'sha256', $tool->name . "\0" . (string) $row['arguments'] ) ) ) {
			self::set_status( (int) $row['id'], self::FAILED );
			return new \WP_Error( 'invalid_proposal', __( 'This proposal can no longer be applied.', 'mindio-magic-ai' ), array( 'status' => 409 ) );
		}

		$permitted = $tool->permitted( $args );
		if ( true !== $permitted ) {
			return is_wp_error( $permitted ) ? self::with_status( $permitted, 403 ) : new \WP_Error( 'forbidden', __( 'You are not allowed to make this change.', 'mindio-magic-ai' ), array( 'status' => 403 ) );
		}

		if ( '' !== (string) $row['fingerprint'] && ! hash_equals( (string) $row['fingerprint'], $tool->fingerprint( $args ) ) ) {
			self::set_status( (int) $row['id'], self::STALE );
			return new \WP_Error( 'stale', __( 'The content changed after this proposal was made, so it was not applied. Ask the assistant to propose the change again.', 'mindio-magic-ai' ), array( 'status' => 409 ) );
		}

		global $wpdb;
		// Claim the proposal; a concurrent apply sees zero affected rows.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic claim.
		$claimed = $wpdb->query( $wpdb->prepare( 'UPDATE %i SET status = %s WHERE id = %d AND status = %s', Installer::table( 'proposals' ), self::APPLYING, (int) $row['id'], self::PENDING ) );
		if ( 1 !== (int) $claimed ) {
			return new \WP_Error( 'not_pending', __( 'This proposal was already handled.', 'mindio-magic-ai' ), array( 'status' => 409 ) );
		}

		$changeset = new Changeset();
		$journal   = $changeset->begin( (string) ( json_decode( (string) $row['preview'], true )['summary'] ?? $tool->label ), $user_id );
		$recorder  = new ChangeRecorder();
		$recorder->start();
		if ( 'woocommerce' === $tool->group && ! empty( $args['id'] ) ) {
			$recorder->track_product( (int) $args['id'] );
		}
		try {
			$result = $tool->run( $args );
		} catch ( \Throwable $e ) {
			$result = new \WP_Error( 'tool_failed', wp_strip_all_tags( $e->getMessage() ) );
		} finally {
			$recorder->stop();
		}

		if ( is_wp_error( $result ) ) {
			self::finish( (int) $row['id'], self::FAILED, '', array( 'error' => $result->get_error_message() ) );
			AuditLog::write( 'proposal.apply', 'proposal', $public_id, array( 'tool' => $tool->name, 'error' => $result->get_error_code() ), false );
			return self::with_status( $result, 400 );
		}

		$entries = $recorder->entries();
		$changeset->record( (string) $journal['changeset_id'], $tool->name, $entries );
		$undoable = $tool->reversible && $entries && ! $recorder->truncated();

		self::finish(
			(int) $row['id'],
			self::APPLIED,
			$undoable ? (string) $journal['changeset_id'] : '',
			array(
				'target'    => $result['target'] ?? null,
				'automatic' => $automatic,
			)
		);
		AuditLog::write(
			'proposal.apply',
			'proposal',
			$public_id,
			array(
				'tool'      => $tool->name,
				'entries'   => count( $entries ),
				'automatic' => $automatic,
				'target'    => is_array( $result['target'] ?? null ) ? ( $result['target']['type'] ?? '' ) . ':' . ( $result['target']['id'] ?? '' ) : '',
			)
		);

		return self::to_public( (array) self::get( $public_id ) );
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function reject( string $public_id, int $user_id ): array|\WP_Error {
		$row = self::get( $public_id );
		if ( ! $row || (int) $row['user_id'] !== $user_id ) {
			return new \WP_Error( 'not_found', __( 'Proposal not found.', 'mindio-magic-ai' ), array( 'status' => 404 ) );
		}
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic transition.
		$updated = $wpdb->query( $wpdb->prepare( 'UPDATE %i SET status = %s, decided_at = %s WHERE id = %d AND status = %s', Installer::table( 'proposals' ), self::REJECTED, current_time( 'mysql', true ), (int) $row['id'], self::PENDING ) );
		if ( 1 !== (int) $updated ) {
			return new \WP_Error( 'not_pending', __( 'This proposal was already handled.', 'mindio-magic-ai' ), array( 'status' => 409 ) );
		}
		AuditLog::write( 'proposal.reject', 'proposal', $public_id, array( 'tool' => (string) $row['tool'] ) );
		return self::to_public( (array) self::get( $public_id ) );
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function undo( string $public_id, int $user_id, bool $force ): array|\WP_Error {
		$row = self::get( $public_id );
		if ( ! $row || ( (int) $row['user_id'] !== $user_id && ! current_user_can( 'manage_options' ) ) ) {
			return new \WP_Error( 'not_found', __( 'Proposal not found.', 'mindio-magic-ai' ), array( 'status' => 404 ) );
		}
		if ( self::APPLIED !== $row['status'] || '' === (string) $row['changeset_id'] ) {
			return new \WP_Error( 'not_undoable', __( 'This change cannot be undone.', 'mindio-magic-ai' ), array( 'status' => 409 ) );
		}
		$result = ( new Changeset() )->revert( (string) $row['changeset_id'], $force );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		self::finish( (int) $row['id'], self::REVERTED, (string) $row['changeset_id'], array_merge( (array) json_decode( (string) $row['result'], true ), array( 'undo' => $result ) ) );
		AuditLog::write( 'proposal.undo', 'proposal', $public_id, array( 'tool' => (string) $row['tool'], 'reverted' => $result['reverted'], 'forced' => $result['forced'] ) );
		return self::to_public( (array) self::get( $public_id ) );
	}

	/**
	 * Decisions the model has not been told about yet, as one note for the next user turn.
	 */
	public static function take_notes( int $conversation_id ): string {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Live state.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, public_id, tool, status FROM %i WHERE conversation_id = %d AND reported = 0 AND status <> 'pending' AND status <> 'applying' ORDER BY id ASC", Installer::table( 'proposals' ), $conversation_id ), ARRAY_A );
		if ( ! $rows ) {
			return '';
		}
		$lines = array();
		foreach ( $rows as $row ) {
			$lines[] = sprintf( '- %s (%s): %s', $row['public_id'], $row['tool'], $row['status'] );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
			$wpdb->update( Installer::table( 'proposals' ), array( 'reported' => 1 ), array( 'id' => (int) $row['id'] ) );
		}
		return "Status of earlier proposals since your last turn:\n" . implode( "\n", $lines );
	}

	/**
	 * @param array<string,mixed> $row
	 * @return array<string,mixed>
	 */
	public static function to_public( array $row ): array {
		$tool   = ToolRegistry::instance()->get( (string) ( $row['tool'] ?? '' ) );
		$result = json_decode( (string) ( $row['result'] ?? '' ), true );
		return array(
			'id'          => (string) ( $row['public_id'] ?? '' ),
			'tool'        => (string) ( $row['tool'] ?? '' ),
			'label'       => $tool ? $tool->label : (string) ( $row['tool'] ?? '' ),
			'risk'        => $tool ? $tool->risk : 'write',
			'reversible'  => $tool ? $tool->reversible : false,
			'status'      => (string) ( $row['status'] ?? '' ),
			'preview'     => json_decode( (string) ( $row['preview'] ?? '' ), true ) ?: array(),
			'result'      => is_array( $result ) ? $result : null,
			'can_undo'    => self::APPLIED === ( $row['status'] ?? '' ) && '' !== (string) ( $row['changeset_id'] ?? '' ),
			'created_at'  => Time::iso( $row['created_at'] ?? '' ),
			'expires_at'  => Time::iso( $row['expires_at'] ?? '' ),
			'decided_at'  => Time::iso( $row['decided_at'] ?? '' ),
		);
	}

	/** @param array<string,mixed> $result */
	private static function finish( int $id, string $status, string $changeset_id, array $result ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
		$wpdb->update(
			Installer::table( 'proposals' ),
			array(
				'status'       => $status,
				'changeset_id' => $changeset_id,
				'result'       => Json::encode( $result ),
				'decided_at'   => current_time( 'mysql', true ),
				'reported'     => 0,
			),
			array( 'id' => $id )
		);
	}

	private static function set_status( int $id, string $status ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
		$wpdb->update( Installer::table( 'proposals' ), array( 'status' => $status, 'decided_at' => current_time( 'mysql', true ) ), array( 'id' => $id ) );
	}

	private static function with_status( \WP_Error $error, int $status ): \WP_Error {
		$data = $error->get_error_data();
		if ( ! is_array( $data ) || ! isset( $data['status'] ) ) {
			$error->add_data( array_merge( is_array( $data ) ? $data : array(), array( 'status' => $status ) ) );
		}
		return $error;
	}
}
