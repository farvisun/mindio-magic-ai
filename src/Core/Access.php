<?php
/**
 * Plugin capabilities, granted at runtime from a role map.
 *
 * Nothing is written to wp_user_roles, so deactivating or uninstalling the
 * plugin leaves no capabilities behind.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Access {
	public const OPTION = 'mindio_magic_ai_access';

	public const USE_ASSISTANT   = 'mindio_ai_use_assistant';
	public const MANAGE_SETTINGS = 'mindio_ai_manage_settings';

	/** @return array<int,string> */
	public static function capabilities(): array {
		return array( self::USE_ASSISTANT, self::MANAGE_SETTINGS );
	}

	/** @return array<string,string> */
	public static function capability_labels(): array {
		return array(
			self::USE_ASSISTANT   => __( 'Use the assistant', 'mindio-magic-ai' ),
			self::MANAGE_SETTINGS => __( 'View usage, budgets, and logs', 'mindio-magic-ai' ),
		);
	}

	public static function register(): void {
		add_filter( 'user_has_cap', array( self::class, 'grant' ), 10, 4 );
	}

	/**
	 * @param array<string,bool> $allcaps
	 * @param array<int,string>  $caps
	 * @param array<int,mixed>   $args
	 * @return array<string,bool>
	 */
	public static function grant( array $allcaps, array $caps, array $args, \WP_User $user ): array {
		unset( $args );
		$ours = array_intersect( $caps, self::capabilities() );
		if ( ! $ours ) {
			return $allcaps;
		}

		if ( ! empty( $allcaps['manage_options'] ) ) {
			foreach ( $ours as $cap ) {
				$allcaps[ $cap ] = true;
			}
			return $allcaps;
		}

		$map = self::map();
		foreach ( (array) $user->roles as $role ) {
			foreach ( (array) ( $map[ $role ] ?? array() ) as $cap ) {
				if ( in_array( $cap, $ours, true ) ) {
					$allcaps[ $cap ] = true;
				}
			}
		}

		return $allcaps;
	}

	/** @return array<string,array<int,string>> */
	public static function map(): array {
		$stored = get_option( self::OPTION, array() );
		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * Replace the role map. Administrators always have every capability, so they are never stored.
	 *
	 * @param array<string,mixed> $input
	 * @return array<string,array<int,string>>
	 */
	public static function update( array $input ): array {
		$roles = wp_roles()->roles;
		$clean = array();
		foreach ( $input as $role => $caps ) {
			$role = sanitize_key( (string) $role );
			if ( ! isset( $roles[ $role ] ) || 'administrator' === $role ) {
				continue;
			}
			$caps = array_values( array_intersect( array_map( 'strval', (array) $caps ), self::capabilities() ) );
			if ( $caps ) {
				$clean[ $role ] = $caps;
			}
		}
		update_option( self::OPTION, $clean, false );
		return $clean;
	}

	/**
	 * Connections, private hosts, and the kill switch are site-administrator-only.
	 */
	public static function can_manage_connections(): bool {
		return is_multisite() ? current_user_can( 'manage_options' ) && ( is_super_admin() || ! self::network_locked() ) : current_user_can( 'manage_options' );
	}

	private static function network_locked(): bool {
		return (bool) apply_filters( 'mindio_magic_ai_network_locks_connections', false );
	}
}
