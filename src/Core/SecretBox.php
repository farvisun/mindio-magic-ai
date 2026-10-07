<?php
/**
 * Authenticated encryption for provider keys and headers at rest.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SecretBox {
	private const CHECK_OPTION = 'mindio_magic_ai_key_check';
	private const CHECK_VALUE  = 'mindio-magic-ai-key-check-v1';

	public static function available(): bool {
		return function_exists( 'sodium_crypto_secretbox' ) || function_exists( 'openssl_encrypt' );
	}

	public static function encrypt( string $plaintext ): string {
		$key = self::key();
		if ( function_exists( 'sodium_crypto_secretbox' ) ) {
			$nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$cipher = sodium_crypto_secretbox( $plaintext, $nonce, $key );
			return 's1:' . self::base64url_encode( $nonce . $cipher );
		}
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			throw new \RuntimeException( 'No supported authenticated-encryption extension is available.' );
		}

		$iv     = random_bytes( 12 );
		$tag    = '';
		$cipher = openssl_encrypt( $plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
		if ( false === $cipher ) {
			throw new \RuntimeException( 'Unable to encrypt a secret.' );
		}
		return 'o1:' . self::base64url_encode( $iv . $tag . $cipher );
	}

	/**
	 * Decrypt a stored value. An empty string means the value is missing or the key changed.
	 */
	public static function decrypt( string $encoded ): string {
		if ( str_starts_with( $encoded, 's1:' ) && function_exists( 'sodium_crypto_secretbox_open' ) ) {
			$raw = self::base64url_decode( substr( $encoded, 3 ) );
			if ( strlen( $raw ) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES ) {
				return '';
			}
			$nonce = substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$plain = sodium_crypto_secretbox_open( substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ), $nonce, self::key() );
			return false === $plain ? '' : $plain;
		}
		if ( str_starts_with( $encoded, 'o1:' ) && function_exists( 'openssl_decrypt' ) ) {
			$raw = self::base64url_decode( substr( $encoded, 3 ) );
			if ( strlen( $raw ) <= 28 ) {
				return '';
			}
			$plain = openssl_decrypt( substr( $raw, 28 ), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, substr( $raw, 0, 12 ), substr( $raw, 12, 16 ) );
			return false === $plain ? '' : $plain;
		}
		return '';
	}

	/**
	 * Store a known value encrypted with the current key, so a later key change is detectable.
	 */
	public static function ensure_key_check(): void {
		if ( ! self::available() ) {
			return;
		}
		$stored = (string) get_option( self::CHECK_OPTION, '' );
		if ( '' === $stored ) {
			update_option( self::CHECK_OPTION, self::encrypt( self::CHECK_VALUE ), false );
		}
	}

	/**
	 * False when the salts or MINDIO_MAGIC_AI_SECRET_KEY changed after keys were saved.
	 */
	public static function key_is_current(): bool {
		$stored = (string) get_option( self::CHECK_OPTION, '' );
		return '' === $stored || self::CHECK_VALUE === self::decrypt( $stored );
	}

	/**
	 * Accept the current key after the administrator re-entered every provider key.
	 */
	public static function reset_key_check(): void {
		delete_option( self::CHECK_OPTION );
		self::ensure_key_check();
	}

	public static function uses_dedicated_key(): bool {
		return defined( 'MINDIO_MAGIC_AI_SECRET_KEY' ) && '' !== (string) constant( 'MINDIO_MAGIC_AI_SECRET_KEY' );
	}

	public static function base64url_encode( string $value ): string {
		return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
	}

	public static function base64url_decode( string $value ): string {
		$padding = strlen( $value ) % 4;
		if ( $padding ) {
			$value .= str_repeat( '=', 4 - $padding );
		}
		$decoded = base64_decode( strtr( $value, '-_', '+/' ), true );
		return false === $decoded ? '' : $decoded;
	}

	private static function key(): string {
		// The context string is frozen: changing it makes every stored secret undecryptable.
		$material = self::uses_dedicated_key() ? (string) constant( 'MINDIO_MAGIC_AI_SECRET_KEY' ) : wp_salt( 'auth' );
		return hash( 'sha256', $material . '|mindio-magic-ai-secret-box', true );
	}
}
