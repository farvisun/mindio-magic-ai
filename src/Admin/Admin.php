<?php
/**
 * Admin menu, the React app, and the assistant drawer available on every admin screen.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Admin;

use MindioMagicAI\AI\Connections;
use MindioMagicAI\Core\Access;
use MindioMagicAI\Core\KillSwitch;
use MindioMagicAI\Core\SecretBox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {
	public const SLUG = 'mindio-ai';

	/** Pages of the app: slug => [capability, route]. */
	private const PAGES = array(
		'mindio-ai'             => array( Access::USE_ASSISTANT, 'assistant' ),
		'mindio-ai-connections' => array( Access::USE_ASSISTANT, 'connections' ),
		'mindio-ai-usage'       => array( Access::MANAGE_SETTINGS, 'usage' ),
		'mindio-ai-logs'        => array( Access::MANAGE_SETTINGS, 'logs' ),
		'mindio-ai-settings'    => array( 'manage_options', 'settings' ),
	);

	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_action( 'admin_notices', array( self::class, 'notices' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MINDIO_MAGIC_AI_FILE ), array( self::class, 'action_links' ) );
		add_filter( 'admin_body_class', array( self::class, 'body_class' ) );
	}

	public static function menu(): void {
		$cap = current_user_can( Access::USE_ASSISTANT ) ? Access::USE_ASSISTANT : Access::MANAGE_SETTINGS;
		add_menu_page(
			__( 'Mindio AI', 'mindio-magic-ai' ),
			__( 'Mindio AI', 'mindio-magic-ai' ),
			$cap,
			self::SLUG,
			array( self::class, 'render' ),
			'data:image/svg+xml;base64,' . base64_encode( self::menu_icon() ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Inline SVG menu icon.
			58
		);

		$labels = array(
			'mindio-ai'             => __( 'Assistant', 'mindio-magic-ai' ),
			'mindio-ai-connections' => __( 'Connections', 'mindio-magic-ai' ),
			'mindio-ai-usage'       => __( 'Usage & budgets', 'mindio-magic-ai' ),
			'mindio-ai-logs'        => __( 'Activity log', 'mindio-magic-ai' ),
			'mindio-ai-settings'    => __( 'Settings', 'mindio-magic-ai' ),
		);
		foreach ( self::PAGES as $slug => $page ) {
			add_submenu_page( self::SLUG, $labels[ $slug ], $labels[ $slug ], $page[0], $slug, array( self::class, 'render' ) );
		}
	}

	public static function render(): void {
		$page  = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : self::SLUG; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing.
		$route = self::PAGES[ $page ][1] ?? 'assistant';
		$embed = self::is_embed();
		printf(
			'<div class="wrap mindio-ai-wrap%1$s"><div id="mindio-ai-root" class="mindio-ai-root" data-route="%2$s"><div class="mindio-ai-boot" role="status"><span class="mindio-ai-boot__spinner" aria-hidden="true"></span>%3$s</div><noscript><p>%4$s</p></noscript></div></div>',
			$embed ? ' is-embed' : '',
			esc_attr( $route ),
			esc_html__( 'Loading Mindio AI…', 'mindio-magic-ai' ),
			esc_html__( 'Mindio AI needs JavaScript.', 'mindio-magic-ai' )
		);
	}

	private static function is_embed(): bool {
		return isset( $_GET['mindio_embed'] ) && '1' === sanitize_key( wp_unslash( $_GET['mindio_embed'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display mode only.
	}

	public static function body_class( string $classes ): string {
		$screen = get_current_screen();
		if ( $screen && self::is_app_screen( $screen->id ) ) {
			$classes .= ' mindio-ai-screen';
			if ( self::is_embed() ) {
				$classes .= ' mindio-ai-embed';
			}
		}
		return $classes;
	}

	private static function is_app_screen( string $screen_id ): bool {
		foreach ( array_keys( self::PAGES ) as $slug ) {
			if ( str_ends_with( $screen_id, '_page_' . $slug ) ) {
				return true;
			}
		}
		return false;
	}

	public static function enqueue( string $hook ): void {
		$screen = get_current_screen();
		if ( $screen && self::is_app_screen( $screen->id ) ) {
			self::enqueue_app();
			return;
		}
		if ( current_user_can( Access::USE_ASSISTANT ) ) {
			self::enqueue_drawer( $hook );
		}
	}

	private static function enqueue_app(): void {
		$asset = self::asset( 'admin' );
		if ( ! $asset ) {
			return;
		}
		wp_enqueue_script( 'mindio-ai-admin', MINDIO_MAGIC_AI_URL . 'build/admin.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( 'mindio-ai-admin', 'mindio-magic-ai' );
		wp_enqueue_style( 'wp-components' );
		wp_enqueue_style( 'mindio-ai-admin', MINDIO_MAGIC_AI_URL . 'build/admin.css', array( 'wp-components' ), $asset['version'] );
		wp_style_add_data( 'mindio-ai-admin', 'rtl', 'replace' );
		self::enqueue_font();

		wp_add_inline_script(
			'mindio-ai-admin',
			'window.mindioAI = ' . wp_json_encode(
				array(
					'adminUrl'  => admin_url(),
					'restRoot'  => esc_url_raw( rest_url( 'mindio-magic-ai/v1/' ) ),
					'nonce'     => wp_create_nonce( 'wp_rest' ),
					'pages'     => array(
						'assistant'   => admin_url( 'admin.php?page=mindio-ai' ),
						'connections' => admin_url( 'admin.php?page=mindio-ai-connections' ),
						'usage'       => admin_url( 'admin.php?page=mindio-ai-usage' ),
						'logs'        => admin_url( 'admin.php?page=mindio-ai-logs' ),
						'settings'    => admin_url( 'admin.php?page=mindio-ai-settings' ),
					),
					'isRtl'     => is_rtl(),
					'locale'    => get_user_locale(),
					'embed'     => self::is_embed(),
					'docsUrl'   => 'https://github.com/farvisun/mindio-magic-ai#readme',
					'linkAllowlist' => array_values( (array) \MindioMagicAI\Core\Settings::get( 'link_allowlist' ) ),
					'version'   => MINDIO_MAGIC_AI_VERSION,
				)
			) . ';',
			'before'
		);
	}

	/**
	 * The drawer is a tiny loader; the full assistant only loads, in an iframe, when the user opens it.
	 */
	private static function enqueue_drawer( string $hook ): void {
		$asset = self::asset( 'drawer' );
		if ( ! $asset ) {
			return;
		}
		// The Command Palette is optional: the loader registers its command only if WordPress already loaded it.
		$deps = array_values( array_diff( $asset['dependencies'], array( 'wp-commands', 'wp-data' ) ) );
		wp_enqueue_script( 'mindio-ai-drawer', MINDIO_MAGIC_AI_URL . 'build/drawer.js', $deps, $asset['version'], true );
		wp_set_script_translations( 'mindio-ai-drawer', 'mindio-magic-ai' );
		wp_enqueue_style( 'mindio-ai-drawer', MINDIO_MAGIC_AI_URL . 'build/drawer.css', array(), $asset['version'] );
		wp_style_add_data( 'mindio-ai-drawer', 'rtl', 'replace' );

		$post_id = 0;
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			$post_id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Context only; the server re-checks access.
		}
		$screen = get_current_screen();

		wp_add_inline_script(
			'mindio-ai-drawer',
			'window.mindioAIDrawer = ' . wp_json_encode(
				array(
					'url'      => add_query_arg( array( 'page' => self::SLUG, 'mindio_embed' => '1' ), admin_url( 'admin.php' ) ),
					'origin'   => self::origin(),
					'screen'   => array(
						'screen'    => $screen ? $screen->id : $hook,
						'post_id'   => $post_id,
						'post_type' => $screen ? (string) $screen->post_type : '',
					),
					'commands' => wp_script_is( 'wp-commands', 'registered' ),
				)
			) . ';',
			'before'
		);
	}

	private static function origin(): string {
		$parts = wp_parse_url( admin_url() );
		return ( $parts['scheme'] ?? 'https' ) . '://' . ( $parts['host'] ?? '' ) . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' );
	}

	private static function enqueue_font(): void {
		if ( ! str_starts_with( get_user_locale(), 'fa' ) ) {
			return;
		}
		wp_enqueue_style( 'mindio-ai-vazirmatn', MINDIO_MAGIC_AI_URL . 'assets/fonts/vazirmatn.css', array(), '33.0.3' );
	}

	/** @return array{dependencies:array<int,string>,version:string}|null */
	private static function asset( string $entry ): ?array {
		$file = MINDIO_MAGIC_AI_DIR . 'build/' . $entry . '.asset.php';
		if ( ! is_readable( $file ) ) {
			return null;
		}
		$asset = require $file;
		return is_array( $asset ) ? $asset : null;
	}

	public static function admin_bar( \WP_Admin_Bar $bar ): void {
		if ( ! is_admin() || ! current_user_can( Access::USE_ASSISTANT ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && self::is_app_screen( $screen->id ) ) {
			return;
		}
		$bar->add_node(
			array(
				'id'    => 'mindio-ai',
				'title' => '<span class="ab-icon" aria-hidden="true"></span><span class="ab-label">' . esc_html__( 'Ask AI', 'mindio-magic-ai' ) . '</span>',
				'href'  => admin_url( 'admin.php?page=' . self::SLUG ),
				'meta'  => array(
					'class' => 'mindio-ai-bar',
					'title' => __( 'Open the Mindio AI assistant', 'mindio-magic-ai' ),
				),
			)
		);
	}

	public static function notices(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && self::is_app_screen( $screen->id ) ) {
			return;
		}
		if ( ! SecretBox::key_is_current() && ( new Connections() )->all() ) {
			printf(
				'<div class="notice notice-error"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
				esc_html__( 'Mindio AI cannot decrypt the saved API keys because the site’s security keys changed. Enter the provider keys again.', 'mindio-magic-ai' ),
				esc_url( admin_url( 'admin.php?page=mindio-ai-connections' ) ),
				esc_html__( 'Open Connections', 'mindio-magic-ai' )
			);
		}
		if ( KillSwitch::active() ) {
			printf(
				'<div class="notice notice-warning"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
				esc_html__( 'Mindio AI is paused: no AI requests are being sent.', 'mindio-magic-ai' ),
				esc_url( admin_url( 'admin.php?page=mindio-ai-usage' ) ),
				esc_html__( 'Manage', 'mindio-magic-ai' )
			);
		}
	}

	/**
	 * @param array<int,string> $links
	 * @return array<int,string>
	 */
	public static function action_links( array $links ): array {
		array_unshift(
			$links,
			sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=mindio-ai-connections' ) ), esc_html__( 'Connections', 'mindio-magic-ai' ) )
		);
		return $links;
	}

	private static function menu_icon(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="black" d="M10 1.5l1.9 4.6 4.6 1.9-4.6 1.9L10 14.5l-1.9-4.6L3.5 8l4.6-1.9L10 1.5zm5.5 10l.9 2.1 2.1.9-2.1.9-.9 2.1-.9-2.1-2.1-.9 2.1-.9.9-2.1zM4.5 13l.7 1.6 1.6.7-1.6.7-.7 1.6-.7-1.6-1.6-.7 1.6-.7.7-1.6z"/></svg>';
	}
}
