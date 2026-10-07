<?php
/**
 * Database installation, upgrades, and retention jobs.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Installer {
	public const CLEANUP_HOOK = 'mindio_magic_ai_cleanup';

	/** Every plugin-owned table, without the site prefix. */
	public const TABLES = array(
		'connections',
		'conversations',
		'messages',
		'runs',
		'run_events',
		'proposals',
		'changesets',
		'changeset_items',
		'usage',
		'counters',
		'audit_log',
	);

	public static function table( string $name ): string {
		global $wpdb;
		return $wpdb->prefix . 'mindio_magic_ai_' . $name;
	}

	public static function activate( bool $network_wide = false ): void {
		if ( is_multisite() && $network_wide ) {
			foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $site_id ) {
				switch_to_blog( (int) $site_id );
				self::install_site();
				restore_current_blog();
			}
			return;
		}
		self::install_site();
	}

	public static function deactivate( bool $network_wide = false ): void {
		if ( is_multisite() && $network_wide ) {
			foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $site_id ) {
				switch_to_blog( (int) $site_id );
				wp_clear_scheduled_hook( self::CLEANUP_HOOK );
				restore_current_blog();
			}
			return;
		}
		wp_clear_scheduled_hook( self::CLEANUP_HOOK );
	}

	public static function initialize_site( \WP_Site $site ): void {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! is_plugin_active_for_network( plugin_basename( MINDIO_MAGIC_AI_FILE ) ) ) {
			return;
		}
		switch_to_blog( (int) $site->blog_id );
		self::install_site();
		restore_current_blog();
	}

	public static function maybe_upgrade(): void {
		if ( MINDIO_MAGIC_AI_DB_VERSION !== (string) get_option( 'mindio_magic_ai_db_version', '' ) ) {
			self::install_site();
		}
	}

	private static function install_site(): void {
		self::install_tables();
		Settings::ensure_defaults();
		SecretBox::ensure_key_check();
		update_option( 'mindio_magic_ai_db_version', MINDIO_MAGIC_AI_DB_VERSION, false );
		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
		}
	}

	/**
	 * Apply retention rules to conversations, run events, usage, counters, and the audit log.
	 */
	public static function cleanup(): void {
		global $wpdb;

		$settings = Settings::all();
		$now      = time();

		$conversation_days = (int) $settings['conversation_retention_days'];
		if ( $conversation_days > 0 ) {
			$cutoff = gmdate( 'Y-m-d H:i:s', $now - $conversation_days * DAY_IN_SECONDS );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Retention over plugin-owned tables.
			$ids = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM %i WHERE updated_at < %s AND pinned = 0 LIMIT 500', self::table( 'conversations' ), $cutoff ) );
			foreach ( (array) $ids as $id ) {
				( new \MindioMagicAI\Assistant\Conversations() )->delete( (int) $id );
			}
		}

		$event_cutoff = gmdate( 'Y-m-d H:i:s', $now - DAY_IN_SECONDS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Retention over plugin-owned tables.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE created_at < %s', self::table( 'run_events' ), $event_cutoff ) );

		$audit_cutoff = gmdate( 'Y-m-d H:i:s', $now - max( 7, (int) $settings['audit_retention_days'] ) * DAY_IN_SECONDS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Retention over plugin-owned tables.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE created_at < %s', self::table( 'audit_log' ), $audit_cutoff ) );

		$usage_cutoff = gmdate( 'Y-m-d H:i:s', $now - 400 * DAY_IN_SECONDS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Retention over plugin-owned tables.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE created_at < %s', self::table( 'usage' ), $usage_cutoff ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Expired counters are recreated on demand.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE expires_at < %s', self::table( 'counters' ), gmdate( 'Y-m-d H:i:s', $now ) ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Expire stale proposals.
		$wpdb->query( $wpdb->prepare( "UPDATE %i SET status = 'expired' WHERE status = 'pending' AND expires_at < %s", self::table( 'proposals' ), gmdate( 'Y-m-d H:i:s', $now ) ) );

		// Runs whose worker died (fatal error, killed process) never finish on their own.
		$stale = gmdate( 'Y-m-d H:i:s', $now - 30 * MINUTE_IN_SECONDS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Mark abandoned runs as failed.
		$wpdb->query( $wpdb->prepare( "UPDATE %i SET status = 'failed', error_code = 'abandoned' WHERE status IN ('queued','running','stopping') AND heartbeat_at < %s", self::table( 'runs' ), $stale ) );
	}

	private static function install_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();

		$schemas = array(
			'connections'     => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(100) NOT NULL DEFAULT '',
				provider varchar(32) NOT NULL,
				preset varchar(32) NOT NULL DEFAULT '',
				base_url varchar(255) NOT NULL DEFAULT '',
				bound_host varchar(255) NOT NULL DEFAULT '',
				enc_key text NULL,
				key_hint varchar(12) NOT NULL DEFAULT '',
				enc_headers text NULL,
				models longtext NULL,
				options longtext NULL,
				status varchar(20) NOT NULL DEFAULT 'needs_key',
				created_by bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY provider (provider)",
			'conversations'   => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				title varchar(191) NOT NULL DEFAULT '',
				connection_id bigint(20) unsigned NOT NULL DEFAULT 0,
				model varchar(100) NOT NULL DEFAULT '',
				pinned tinyint(1) NOT NULL DEFAULT 0,
				archived tinyint(1) NOT NULL DEFAULT 0,
				forked_from bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY user_updated (user_id,updated_at)",
			'messages'        => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				conversation_id bigint(20) unsigned NOT NULL,
				run_id bigint(20) unsigned NOT NULL DEFAULT 0,
				role varchar(16) NOT NULL,
				content longtext NULL,
				raw longtext NULL,
				provider varchar(32) NOT NULL DEFAULT '',
				model varchar(100) NOT NULL DEFAULT '',
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY conversation (conversation_id,id)",
			'runs'            => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				conversation_id bigint(20) unsigned NOT NULL DEFAULT 0,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				feature varchar(40) NOT NULL DEFAULT 'assistant',
				status varchar(16) NOT NULL DEFAULT 'queued',
				steps smallint(5) unsigned NOT NULL DEFAULT 0,
				input_tokens int(10) unsigned NOT NULL DEFAULT 0,
				output_tokens int(10) unsigned NOT NULL DEFAULT 0,
				cost_micros bigint(20) unsigned NOT NULL DEFAULT 0,
				tainted tinyint(1) NOT NULL DEFAULT 0,
				context longtext NULL,
				error_code varchar(64) NOT NULL DEFAULT '',
				error_message text NULL,
				created_at datetime NOT NULL,
				heartbeat_at datetime NOT NULL,
				finished_at datetime NULL,
				PRIMARY KEY  (id),
				KEY conversation (conversation_id),
				KEY status (status)",
			'run_events'      => 'id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				run_id bigint(20) unsigned NOT NULL,
				type varchar(24) NOT NULL,
				payload longtext NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY run (run_id,id),
				KEY created_at (created_at)',
			'proposals'       => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_id varchar(40) NOT NULL,
				run_id bigint(20) unsigned NOT NULL DEFAULT 0,
				conversation_id bigint(20) unsigned NOT NULL DEFAULT 0,
				user_id bigint(20) unsigned NOT NULL,
				tool varchar(80) NOT NULL,
				arguments longtext NULL,
				args_hash char(64) NOT NULL,
				fingerprint varchar(128) NOT NULL DEFAULT '',
				preview longtext NULL,
				status varchar(16) NOT NULL DEFAULT 'pending',
				changeset_id varchar(40) NOT NULL DEFAULT '',
				result longtext NULL,
				reported tinyint(1) NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				expires_at datetime NOT NULL,
				decided_at datetime NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY public_id (public_id),
				KEY user_status (user_id,status),
				KEY conversation (conversation_id)",
			'changesets'      => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				changeset_id varchar(40) NOT NULL,
				label varchar(191) NOT NULL DEFAULT '',
				status varchar(20) NOT NULL DEFAULT 'open',
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				entries int(10) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY changeset_id (changeset_id),
				KEY user_id (user_id)",
			'changeset_items' => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				changeset_id varchar(40) NOT NULL,
				tool varchar(80) NOT NULL DEFAULT '',
				kind varchar(32) NOT NULL,
				object_key varchar(191) NOT NULL,
				operation varchar(20) NOT NULL DEFAULT 'update',
				target text NULL,
				before_state longtext NULL,
				after_state longtext NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY changeset_id (changeset_id)",
			'usage'           => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				created_at datetime NOT NULL,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				feature varchar(40) NOT NULL DEFAULT '',
				connection_id bigint(20) unsigned NOT NULL DEFAULT 0,
				provider varchar(32) NOT NULL DEFAULT '',
				model varchar(100) NOT NULL DEFAULT '',
				run_id bigint(20) unsigned NOT NULL DEFAULT 0,
				input_tokens int(10) unsigned NOT NULL DEFAULT 0,
				output_tokens int(10) unsigned NOT NULL DEFAULT 0,
				cache_read_tokens int(10) unsigned NOT NULL DEFAULT 0,
				cache_write_tokens int(10) unsigned NOT NULL DEFAULT 0,
				cost_micros bigint(20) unsigned NOT NULL DEFAULT 0,
				estimated tinyint(1) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY created_at (created_at),
				KEY feature (feature)",
			'counters'        => "bucket varchar(191) NOT NULL,
				value bigint(20) NOT NULL DEFAULT 0,
				expires_at datetime NOT NULL,
				PRIMARY KEY  (bucket),
				KEY expires_at (expires_at)",
			'audit_log'       => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				created_at datetime NOT NULL,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				event varchar(64) NOT NULL,
				object_type varchar(32) NOT NULL DEFAULT '',
				object_id varchar(64) NOT NULL DEFAULT '',
				success tinyint(1) NOT NULL DEFAULT 1,
				details longtext NULL,
				ip varchar(45) NOT NULL DEFAULT '',
				PRIMARY KEY  (id),
				KEY created_at (created_at),
				KEY event (event),
				KEY user_id (user_id)",
		);

		foreach ( $schemas as $name => $columns ) {
			dbDelta( 'CREATE TABLE ' . self::table( $name ) . " (\n" . $columns . "\n) $charset;" );
		}
	}
}
