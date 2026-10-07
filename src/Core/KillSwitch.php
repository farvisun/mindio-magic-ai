<?php
/**
 * Site-wide pause for every AI call.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class KillSwitch {
	public const OPTION = 'mindio_magic_ai_kill_switch';

	public static function active(): bool {
		if ( defined( 'MINDIO_MAGIC_AI_DISABLED' ) && constant( 'MINDIO_MAGIC_AI_DISABLED' ) ) {
			return true;
		}
		return (bool) get_option( self::OPTION, false );
	}

	public static function locked_by_constant(): bool {
		return defined( 'MINDIO_MAGIC_AI_DISABLED' ) && (bool) constant( 'MINDIO_MAGIC_AI_DISABLED' );
	}

	public static function set( bool $active ): void {
		update_option( self::OPTION, $active, true );
		AuditLog::write( $active ? 'killswitch.on' : 'killswitch.off', 'site', (string) get_current_blog_id() );
	}
}
