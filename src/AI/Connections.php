<?php
/**
 * Stores provider connections. Keys are write-only and pinned to the host they were saved for.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI;

use MindioMagicAI\Core\AuditLog;
use MindioMagicAI\Core\Installer;
use MindioMagicAI\Core\SecretBox;
use MindioMagicAI\Core\Settings;
use MindioMagicAI\Core\UrlGuard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Connections {
	private const FORBIDDEN_HEADERS = array( 'host', 'content-length', 'transfer-encoding', 'connection', 'content-type', 'authorization', 'x-api-key', 'anthropic-version', 'x-goog-api-key' );

	/** @return array<int,Connection> */
	public function all(): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id ASC', Installer::table( 'connections' ) ), ARRAY_A );
		return array_map( array( Connection::class, 'from_row' ), (array) $rows );
	}

	public function get( int $id ): ?Connection {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', Installer::table( 'connections' ), $id ), ARRAY_A );
		return is_array( $row ) ? Connection::from_row( $row ) : null;
	}

	/**
	 * The connection used by a feature: the configured one, else the first usable connection.
	 */
	public function for_assistant(): ?Connection {
		$configured = (int) Settings::get( 'assistant_connection' );
		if ( $configured ) {
			$connection = $this->get( $configured );
			if ( $connection && $connection->has_key() ) {
				return $connection;
			}
		}
		foreach ( $this->all() as $connection ) {
			if ( $connection->has_key() ) {
				return $connection;
			}
		}
		return null;
	}

	/**
	 * @param array<string,mixed> $input
	 */
	public function create( array $input ): Connection|\WP_Error {
		$preset_id = sanitize_key( (string) ( $input['preset'] ?? '' ) );
		$preset    = Presets::get( $preset_id );
		if ( ! $preset ) {
			return new \WP_Error( 'invalid_preset', __( 'Choose a provider.', 'mindio-magic-ai' ), array( 'status' => 400 ) );
		}

		$base_url = $preset['editable_url'] && ! empty( $input['base_url'] ) ? (string) $input['base_url'] : $preset['base_url'];
		$base_url = $this->normalize_url( $base_url );
		$host     = $this->validate_host( $base_url );
		if ( is_wp_error( $host ) ) {
			return $host;
		}

		$headers = $this->sanitize_headers( $input['headers'] ?? array() );
		if ( is_wp_error( $headers ) ) {
			return $headers;
		}

		$key = trim( (string) ( $input['api_key'] ?? '' ) );
		$now = current_time( 'mysql', true );
		$row = array(
			'name'        => $this->name( $input['name'] ?? '', $preset['label'] ),
			'provider'    => $preset['provider'],
			'preset'      => $preset_id,
			'base_url'    => $base_url,
			'bound_host'  => $host,
			'enc_key'     => '' === $key ? '' : SecretBox::encrypt( $key ),
			'key_hint'    => $this->hint( $key ),
			'enc_headers' => $headers ? SecretBox::encrypt( (string) wp_json_encode( $headers ) ) : '',
			'models'      => (string) wp_json_encode( $this->models( $input['models'] ?? array(), $preset ) ),
			'options'     => (string) wp_json_encode( $this->options( $input['options'] ?? array() ) ),
			'status'      => 'active',
			'created_by'  => get_current_user_id(),
			'created_at'  => $now,
			'updated_at'  => $now,
		);

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned table.
		$wpdb->insert( Installer::table( 'connections' ), $row );
		$id = (int) $wpdb->insert_id;

		$connection = $this->get( $id );
		if ( ! $connection ) {
			return new \WP_Error( 'db_error', __( 'The connection could not be saved.', 'mindio-magic-ai' ), array( 'status' => 500 ) );
		}
		$this->refresh_status( $connection );
		AuditLog::write( 'connection.create', 'connection', (string) $id, array( 'preset' => $preset_id, 'host' => $host, 'key_source' => $connection->key_source() ) );

		return $this->get( $id ) ?? $connection;
	}

	/**
	 * Update a connection. Changing the provider or address discards the stored key
	 * unless a new key is supplied in the same request.
	 *
	 * @param array<string,mixed> $input
	 */
	public function update( int $id, array $input ): Connection|\WP_Error {
		$current = $this->get( $id );
		if ( ! $current ) {
			return new \WP_Error( 'not_found', __( 'Connection not found.', 'mindio-magic-ai' ), array( 'status' => 404 ) );
		}
		$preset = Presets::get( $current->preset ) ?? Presets::get( 'custom' );

		$changes  = array( 'updated_at' => current_time( 'mysql', true ) );
		$audit    = array();
		$base_url = $current->base_url;

		if ( array_key_exists( 'base_url', $input ) && $preset['editable_url'] ) {
			$base_url = $this->normalize_url( (string) $input['base_url'] );
			$host     = $this->validate_host( $base_url );
			if ( is_wp_error( $host ) ) {
				return $host;
			}
			if ( $base_url !== $current->base_url ) {
				$changes['base_url']   = $base_url;
				$changes['bound_host'] = $host;
				$audit['host']         = $host;
				if ( $host !== $current->bound_host || $this->port_of( $base_url ) !== $this->port_of( $current->base_url ) ) {
					// The saved key belongs to the previous host and must not follow the connection.
					$changes['enc_key']     = '';
					$changes['key_hint']    = '';
					$changes['enc_headers'] = '';
					$audit['key_cleared']   = true;
				}
			}
		}

		if ( isset( $input['api_key'] ) && '' !== trim( (string) $input['api_key'] ) ) {
			$key                 = trim( (string) $input['api_key'] );
			$changes['enc_key']  = SecretBox::encrypt( $key );
			$changes['key_hint'] = $this->hint( $key );
			$audit['key']        = 'replaced';
		}
		if ( ! empty( $input['remove_key'] ) ) {
			$changes['enc_key']  = '';
			$changes['key_hint'] = '';
			$audit['key']        = 'removed';
		}
		if ( array_key_exists( 'headers', $input ) && ! isset( $audit['key_cleared'] ) ) {
			$headers = $this->sanitize_headers( $input['headers'] );
			if ( is_wp_error( $headers ) ) {
				return $headers;
			}
			// Header values are write-only too: a name without a value keeps the stored value.
			$merged = array();
			$stored = $current->extra_headers();
			foreach ( $headers as $name => $value ) {
				$merged[ $name ] = '' === $value ? (string) ( $stored[ $name ] ?? '' ) : $value;
			}
			$merged                 = array_filter( $merged, static fn( string $v ): bool => '' !== $v );
			$changes['enc_headers'] = $merged ? SecretBox::encrypt( (string) wp_json_encode( $merged ) ) : '';
			$audit['headers']       = array_keys( $merged );
		}
		if ( array_key_exists( 'name', $input ) ) {
			$changes['name'] = $this->name( $input['name'], $current->name );
		}
		if ( array_key_exists( 'models', $input ) ) {
			$changes['models'] = (string) wp_json_encode( $this->models( $input['models'], $preset, $current->models ) );
		}
		if ( array_key_exists( 'options', $input ) ) {
			$changes['options'] = (string) wp_json_encode( $this->options( $input['options'] ) );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
		$wpdb->update( Installer::table( 'connections' ), $changes, array( 'id' => $id ) );

		$updated = $this->get( $id );
		if ( $updated ) {
			$this->refresh_status( $updated );
		}
		AuditLog::write( 'connection.update', 'connection', (string) $id, $audit );

		return $this->get( $id ) ?? $current;
	}

	public function delete( int $id ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
		$deleted = (bool) $wpdb->delete( Installer::table( 'connections' ), array( 'id' => $id ), array( '%d' ) );
		if ( $deleted ) {
			if ( (int) Settings::get( 'assistant_connection' ) === $id ) {
				Settings::update( array( 'assistant_connection' => 0 ) );
			}
			AuditLog::write( 'connection.delete', 'connection', (string) $id );
		}
		return $deleted;
	}

	/**
	 * Store the model list fetched from the provider.
	 *
	 * @param array<int,string> $ids
	 */
	public function cache_models( Connection $connection, array $ids ): void {
		$models              = $connection->models;
		$models['available'] = array_slice( array_values( array_unique( array_map( array( Settings::class, 'model_id' ), $ids ) ) ), 0, 500 );
		$models['fetched']   = time();

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
		$wpdb->update( Installer::table( 'connections' ), array( 'models' => (string) wp_json_encode( $models ) ), array( 'id' => $connection->id ) );
	}

	/**
	 * Shape a connection for the admin UI. Never includes a key or header value.
	 *
	 * @return array<string,mixed>
	 */
	public function to_public( Connection $connection ): array {
		$preset = Presets::get( $connection->preset );
		return array(
			'id'           => $connection->id,
			'name'         => $connection->name,
			'provider'     => $connection->provider,
			'preset'       => $connection->preset,
			'preset_label' => (string) ( $preset['label'] ?? $connection->preset ),
			'base_url'     => $connection->base_url,
			'bound_host'   => $connection->bound_host,
			'editable_url' => (bool) ( $preset['editable_url'] ?? true ),
			'key_hint'     => 'constant' === $connection->key_source() ? '' : $connection->key_hint,
			'key_source'   => $connection->key_source(),
			'header_names' => $connection->extra_header_names(),
			'models'       => array(
				'default'   => $connection->default_model(),
				'fast'      => (string) ( $connection->models['fast'] ?? '' ),
				'available' => array_values( (array) ( $connection->models['available'] ?? ( $preset['suggested'] ?? array() ) ) ),
				'fetched'   => (int) ( $connection->models['fetched'] ?? 0 ),
			),
			'options'      => array(
				'timeout'          => (int) $connection->timeout(),
				'max_tokens_param' => $connection->max_tokens_param(),
			),
			'status'       => $connection->status,
			'capabilities' => ModelCapabilities::resolve( $connection->provider, $connection->default_model() ),
		);
	}

	private function refresh_status( Connection $connection ): void {
		$status = $connection->has_key() ? 'active' : 'needs_key';
		if ( '' === $connection->default_model() && $connection->has_key() ) {
			$status = 'needs_model';
		}
		if ( $status === $connection->status ) {
			return;
		}
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
		$wpdb->update( Installer::table( 'connections' ), array( 'status' => $status ), array( 'id' => $connection->id ) );
	}

	private function validate_host( string $base_url ): string|\WP_Error {
		if ( '' === $base_url ) {
			return new \WP_Error( 'invalid_url', __( 'Enter the API base URL.', 'mindio-magic-ai' ), array( 'status' => 400 ) );
		}
		$checked = UrlGuard::check( $base_url, (array) Settings::get( 'private_hosts' ) );
		if ( is_wp_error( $checked ) ) {
			$checked->add_data( array( 'status' => 400 ) );
			return $checked;
		}
		return $checked['host'];
	}

	private function normalize_url( string $url ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return '';
		}
		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['query'] ) || isset( $parts['fragment'] ) ) {
			// Left as-is so validation rejects it with a specific reason.
			return $url;
		}
		$scheme = strtolower( (string) ( $parts['scheme'] ?? 'https' ) );
		$host   = strtolower( (string) $parts['host'] );
		$port   = isset( $parts['port'] ) ? ':' . (int) $parts['port'] : '';
		$path   = rtrim( (string) ( $parts['path'] ?? '' ), '/' );
		return $scheme . '://' . $host . $port . $path;
	}

	private function port_of( string $url ): int {
		$parts = wp_parse_url( $url );
		if ( isset( $parts['port'] ) ) {
			return (int) $parts['port'];
		}
		return 'http' === strtolower( (string) ( $parts['scheme'] ?? 'https' ) ) ? 80 : 443;
	}

	/**
	 * @return array<string,string>|\WP_Error
	 */
	private function sanitize_headers( mixed $input ): array|\WP_Error {
		$out = array();
		foreach ( (array) $input as $key => $value ) {
			if ( is_array( $value ) && isset( $value['name'] ) ) {
				$name  = (string) $value['name'];
				$value = (string) ( $value['value'] ?? '' );
			} else {
				$name  = (string) $key;
				$value = (string) $value;
			}
			$name = trim( $name );
			if ( '' === $name ) {
				continue;
			}
			if ( ! preg_match( "/^[A-Za-z0-9!#$%&'*+.^_`|~-]{1,64}$/", $name ) || in_array( strtolower( $name ), self::FORBIDDEN_HEADERS, true ) ) {
				return new \WP_Error(
					'invalid_header',
					/* translators: %s: HTTP header name. */
					sprintf( __( 'The header "%s" is not allowed.', 'mindio-magic-ai' ), $name ),
					array( 'status' => 400 )
				);
			}
			if ( preg_match( '/[\r\n\0]/', $value ) || strlen( $value ) > 2048 ) {
				return new \WP_Error( 'invalid_header', __( 'Header values cannot contain line breaks or exceed 2048 characters.', 'mindio-magic-ai' ), array( 'status' => 400 ) );
			}
			$out[ $name ] = $value;
			if ( count( $out ) >= 10 ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * @param array<string,mixed> $preset
	 * @param array<string,mixed> $current
	 * @return array<string,mixed>
	 */
	private function models( mixed $input, array $preset, array $current = array() ): array {
		$input  = is_array( $input ) ? $input : array();
		$models = $current;
		foreach ( array( 'default', 'fast' ) as $slot ) {
			if ( array_key_exists( $slot, $input ) ) {
				$models[ $slot ] = Settings::model_id( $input[ $slot ] );
			} elseif ( ! isset( $models[ $slot ] ) ) {
				$models[ $slot ] = 'default' === $slot ? (string) $preset['default_model'] : (string) $preset['fast_model'];
			}
		}
		return $models;
	}

	/** @return array<string,mixed> */
	private function options( mixed $input ): array {
		$input = is_array( $input ) ? $input : array();
		$out   = array( 'timeout' => max( 10, min( 600, absint( $input['timeout'] ?? 120 ) ) ) );
		if ( in_array( $input['max_tokens_param'] ?? '', array( 'max_tokens', 'max_completion_tokens' ), true ) ) {
			$out['max_tokens_param'] = $input['max_tokens_param'];
		}
		return $out;
	}

	private function name( mixed $name, string $fallback ): string {
		$name = trim( sanitize_text_field( (string) $name ) );
		return mb_substr( '' === $name ? $fallback : $name, 0, 100 );
	}

	private function hint( string $key ): string {
		return '' === $key ? '' : substr( $key, -4 );
	}
}
