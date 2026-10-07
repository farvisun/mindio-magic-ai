<?php
/**
 * Optional full data removal, only when the administrator opted in.
 *
 * @package MindioMagicAI
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove every option, table, user preference, and scheduled job the plugin created on the current site.
 */
function mindio_magic_ai_uninstall_site(): void {
	$settings = get_option( 'mindio_magic_ai_settings', array() );
	if ( empty( $settings['delete_on_uninstall'] ) ) {
		return;
	}

	wp_clear_scheduled_hook( 'mindio_magic_ai_cleanup' );

	foreach (
		array(
			'mindio_magic_ai_settings',
			'mindio_magic_ai_access',
			'mindio_magic_ai_budgets',
			'mindio_magic_ai_prices',
			'mindio_magic_ai_kill_switch',
			'mindio_magic_ai_key_check',
			'mindio_magic_ai_db_version',
		) as $option
	) {
		delete_option( $option );
	}

	delete_metadata( 'user', 0, 'mindio_magic_ai_auto_apply', '', true );

	global $wpdb;
	foreach ( array( 'connections', 'conversations', 'messages', 'runs', 'run_events', 'proposals', 'changesets', 'changeset_items', 'usage', 'counters', 'audit_log' ) as $table ) {
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'mindio_magic_ai_' . $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Opted-in uninstall removes the plugin's own tables.
	}
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $mindio_magic_ai_site_id ) {
		switch_to_blog( (int) $mindio_magic_ai_site_id );
		mindio_magic_ai_uninstall_site();
		restore_current_blog();
	}
} else {
	mindio_magic_ai_uninstall_site();
}
