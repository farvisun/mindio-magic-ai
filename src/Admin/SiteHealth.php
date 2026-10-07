<?php
/**
 * Site Health checks: encryption, key integrity, and outbound HTTPS.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Admin;

use MindioMagicAI\AI\Connections;
use MindioMagicAI\Core\KillSwitch;
use MindioMagicAI\Core\SecretBox;
use MindioMagicAI\Core\UrlGuard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SiteHealth {
	public static function register(): void {
		add_filter( 'site_status_tests', array( self::class, 'tests' ) );
		add_filter( 'debug_information', array( self::class, 'debug_information' ) );
	}

	/**
	 * @param array<string,mixed> $tests
	 * @return array<string,mixed>
	 */
	public static function tests( array $tests ): array {
		$tests['direct']['mindio_ai_encryption'] = array(
			'label' => __( 'Mindio AI key encryption', 'mindio-magic-ai' ),
			'test'  => array( self::class, 'encryption' ),
		);
		$tests['direct']['mindio_ai_keys'] = array(
			'label' => __( 'Mindio AI saved keys', 'mindio-magic-ai' ),
			'test'  => array( self::class, 'keys' ),
		);
		return $tests;
	}

	/** @return array<string,mixed> */
	public static function encryption(): array {
		$ok = SecretBox::available();
		return array(
			'label'       => $ok ? __( 'Provider keys are encrypted at rest', 'mindio-magic-ai' ) : __( 'Provider keys cannot be encrypted', 'mindio-magic-ai' ),
			'status'      => $ok ? 'good' : 'critical',
			'badge'       => array(
				'label' => __( 'Security', 'mindio-magic-ai' ),
				'color' => $ok ? 'blue' : 'red',
			),
			'description' => '<p>' . esc_html(
				$ok
					? ( SecretBox::uses_dedicated_key() ? __( 'Keys are encrypted with the MINDIO_MAGIC_AI_SECRET_KEY constant.', 'mindio-magic-ai' ) : __( 'Keys are encrypted with a key derived from the site’s security salts. Define MINDIO_MAGIC_AI_SECRET_KEY in wp-config.php to keep keys readable if the salts are rotated.', 'mindio-magic-ai' ) )
					: __( 'Install the sodium or OpenSSL PHP extension so Mindio AI can encrypt provider keys.', 'mindio-magic-ai' )
			) . '</p>',
			'test'        => 'mindio_ai_encryption',
		);
	}

	/** @return array<string,mixed> */
	public static function keys(): array {
		$ok = SecretBox::key_is_current() || ! ( new Connections() )->all();
		return array(
			'label'       => $ok ? __( 'Saved provider keys can be decrypted', 'mindio-magic-ai' ) : __( 'Saved provider keys can no longer be decrypted', 'mindio-magic-ai' ),
			'status'      => $ok ? 'good' : 'critical',
			'badge'       => array(
				'label' => __( 'Security', 'mindio-magic-ai' ),
				'color' => $ok ? 'blue' : 'red',
			),
			'description' => '<p>' . esc_html( $ok ? __( 'The encryption key has not changed since the keys were saved.', 'mindio-magic-ai' ) : __( 'The site’s security keys changed after provider keys were saved. Enter each key again under Mindio AI → Connections.', 'mindio-magic-ai' ) ) . '</p>',
			'test'        => 'mindio_ai_keys',
		);
	}

	/**
	 * @param array<string,mixed> $info
	 * @return array<string,mixed>
	 */
	public static function debug_information( array $info ): array {
		$connections = ( new Connections() )->all();
		$info['mindio-magic-ai'] = array(
			'label'  => __( 'Mindio Magic AI', 'mindio-magic-ai' ),
			'fields' => array(
				'version'     => array(
					'label' => __( 'Version', 'mindio-magic-ai' ),
					'value' => MINDIO_MAGIC_AI_VERSION,
				),
				'connections' => array(
					'label' => __( 'Connections', 'mindio-magic-ai' ),
					'value' => implode( ', ', array_map( static fn( $c ): string => $c->preset . ' (' . $c->bound_host . ', ' . $c->key_source() . ')', $connections ) ) ?: '—',
				),
				'paused'      => array(
					'label' => __( 'Paused', 'mindio-magic-ai' ),
					'value' => KillSwitch::active() ? __( 'Yes', 'mindio-magic-ai' ) : __( 'No', 'mindio-magic-ai' ),
				),
				'proxy'       => array(
					'label' => __( 'Outbound proxy', 'mindio-magic-ai' ),
					'value' => UrlGuard::proxy_active() ? __( 'Yes (DNS pinning is handled by the proxy)', 'mindio-magic-ai' ) : __( 'No', 'mindio-magic-ai' ),
				),
			),
		);
		return $info;
	}
}
