<?php
/**
 * A saved provider connection. Secrets are decrypted only when a request is made.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI;

use MindioMagicAI\Core\SecretBox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Connection {
	/**
	 * @param array<string,mixed> $models
	 * @param array<string,mixed> $options
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $name,
		public readonly string $provider,
		public readonly string $preset,
		public readonly string $base_url,
		public readonly string $bound_host,
		private readonly string $enc_key,
		public readonly string $key_hint,
		private readonly string $enc_headers,
		public readonly array $models,
		public readonly array $options,
		public readonly string $status,
	) {}

	/** @param array<string,mixed> $row */
	public static function from_row( array $row ): self {
		return new self(
			(int) $row['id'],
			(string) $row['name'],
			(string) $row['provider'],
			(string) $row['preset'],
			(string) $row['base_url'],
			(string) $row['bound_host'],
			(string) ( $row['enc_key'] ?? '' ),
			(string) $row['key_hint'],
			(string) ( $row['enc_headers'] ?? '' ),
			json_decode( (string) $row['models'], true ) ?: array(),
			json_decode( (string) $row['options'], true ) ?: array(),
			(string) $row['status'],
		);
	}

	/**
	 * The API key: a wp-config constant when the connection uses the preset's own host, else the stored key.
	 */
	public function api_key(): string {
		$constant = $this->constant_key();
		if ( '' !== $constant ) {
			return $constant;
		}
		return '' === $this->enc_key ? '' : SecretBox::decrypt( $this->enc_key );
	}

	public function key_source(): string {
		if ( '' !== $this->constant_key() ) {
			return 'constant';
		}
		return '' === $this->enc_key ? 'none' : 'stored';
	}

	public function has_key(): bool {
		return '' !== $this->api_key();
	}

	/** @return array<string,string> */
	public function extra_headers(): array {
		if ( '' === $this->enc_headers ) {
			return array();
		}
		$decoded = json_decode( SecretBox::decrypt( $this->enc_headers ), true );
		return is_array( $decoded ) ? array_map( 'strval', $decoded ) : array();
	}

	/** @return array<int,string> */
	public function extra_header_names(): array {
		return array_keys( $this->extra_headers() );
	}

	public function default_model(): string {
		return (string) ( $this->models['default'] ?? '' );
	}

	public function fast_model(): string {
		$fast = (string) ( $this->models['fast'] ?? '' );
		return '' !== $fast ? $fast : $this->default_model();
	}

	public function timeout(): float {
		return (float) max( 10, min( 600, (int) ( $this->options['timeout'] ?? 120 ) ) );
	}

	public function max_tokens_param(): string {
		$param = (string) ( $this->options['max_tokens_param'] ?? '' );
		if ( in_array( $param, array( 'max_tokens', 'max_completion_tokens' ), true ) ) {
			return $param;
		}
		$preset = Presets::get( $this->preset );
		return (string) ( $preset['max_tokens_param'] ?? 'max_tokens' );
	}

	public function is_official_anthropic(): bool {
		return Presets::PROVIDER_ANTHROPIC === $this->provider && 'api.anthropic.com' === $this->bound_host;
	}

	private function constant_key(): string {
		$preset = Presets::get( $this->preset );
		if ( ! $preset || '' === $preset['constant'] || ! defined( $preset['constant'] ) ) {
			return '';
		}
		// A constant key never follows a connection to a host other than the preset's own.
		if ( untrailingslashit( $preset['base_url'] ) !== untrailingslashit( $this->base_url ) ) {
			return '';
		}
		return (string) constant( $preset['constant'] );
	}
}
