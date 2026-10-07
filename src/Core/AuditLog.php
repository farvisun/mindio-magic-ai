<?php
/**
 * Append-only audit trail for configuration changes, AI runs, and applied changes.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AuditLog {
	private const SENSITIVE = '/(?:password|secret|token|authorization|api[_-]?key|key|headers|content|prompt|message|text|email|phone|data)$/i';

	/**
	 * @param array<string,mixed> $details
	 */
	public static function write( string $event, string $object_type = '', string $object_id = '', array $details = array(), bool $success = true ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Append-only write to the plugin-owned audit table.
		$wpdb->insert(
			Installer::table( 'audit_log' ),
			array(
				'created_at'  => current_time( 'mysql', true ),
				'user_id'     => get_current_user_id(),
				'event'       => substr( preg_replace( '/[^a-z0-9_.-]/', '', strtolower( $event ) ) ?? '', 0, 64 ),
				'object_type' => sanitize_key( $object_type ),
				'object_id'   => substr( sanitize_text_field( $object_id ), 0, 64 ),
				'success'     => $success ? 1 : 0,
				'details'     => wp_json_encode( self::redact( $details ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
				'ip'          => self::request_ip(),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
		);
	}

	/**
	 * @param array<string,mixed> $args
	 * @return array{items:array<int,array<string,mixed>>,total:int}
	 */
	public static function query( array $args = array() ): array {
		global $wpdb;

		$limit  = max( 1, min( 500, absint( $args['per_page'] ?? 50 ) ) );
		$page   = max( 1, absint( $args['page'] ?? 1 ) );
		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['event'] ) ) {
			$where[]  = 'event LIKE %s';
			$params[] = $wpdb->esc_like( sanitize_text_field( (string) $args['event'] ) ) . '%';
		}
		if ( isset( $args['success'] ) && '' !== $args['success'] ) {
			$where[]  = 'success = %d';
			$params[] = rest_sanitize_boolean( $args['success'] ) ? 1 : 0;
		}
		if ( ! empty( $args['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$params[] = absint( $args['user_id'] );
		}
		if ( ! empty( $args['since'] ) ) {
			$where[]  = 'created_at >= %s';
			$params[] = gmdate( 'Y-m-d H:i:s', (int) $args['since'] );
		}

		$table   = Installer::table( 'audit_log' );
		$clause  = implode( ' AND ', $where );
		$count   = 'SELECT COUNT(*) FROM %i WHERE ' . $clause; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Fixed fragments only.
		$select  = 'SELECT * FROM %i WHERE ' . $clause . ' ORDER BY id DESC LIMIT %d OFFSET %d'; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Fixed fragments only.
		$total   = (int) $wpdb->get_var( $wpdb->prepare( $count, array_merge( array( $table ), $params ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Fixed fragments; values pass through prepare.
		$rows    = $wpdb->get_results( $wpdb->prepare( $select, array_merge( array( $table ), $params, array( $limit, ( $page - 1 ) * $limit ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Fixed fragments; values pass through prepare.

		$users = array();
		$items = array();
		foreach ( (array) $rows as $row ) {
			$user_id = (int) $row['user_id'];
			if ( ! isset( $users[ $user_id ] ) ) {
				$user              = $user_id ? get_userdata( $user_id ) : false;
				$users[ $user_id ] = $user instanceof \WP_User ? $user->display_name : '';
			}
			$items[] = array(
				'id'          => (int) $row['id'],
				'created_at'  => Time::iso( (string) $row['created_at'] ),
				'user_id'     => $user_id,
				'user'        => $users[ $user_id ],
				'event'       => (string) $row['event'],
				'object_type' => (string) $row['object_type'],
				'object_id'   => (string) $row['object_id'],
				'success'     => (bool) $row['success'],
				'details'     => json_decode( (string) $row['details'], true ) ?: array(),
				'ip'          => (string) $row['ip'],
			);
		}

		return array(
			'items' => $items,
			'total' => $total,
		);
	}

	public static function redact( mixed $value, string $key = '' ): mixed {
		if ( '' !== $key && preg_match( self::SENSITIVE, $key ) ) {
			return '[redacted]';
		}
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $child_key => $child ) {
				$out[ $child_key ] = self::redact( $child, (string) $child_key );
			}
			return $out;
		}
		if ( is_string( $value ) && mb_strlen( $value ) > 300 ) {
			return mb_substr( $value, 0, 300 ) . '…';
		}
		return $value;
	}

	public static function request_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return false !== filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}
}
