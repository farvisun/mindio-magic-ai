<?php
/**
 * Bootstraps the plugin.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Core;

use MindioMagicAI\Admin\Admin;
use MindioMagicAI\Admin\Privacy;
use MindioMagicAI\Admin\SiteHealth;
use MindioMagicAI\Rest\Routes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {
	private static bool $booted = false;

	public static function boot(): void {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		Installer::maybe_upgrade();
		Access::register();

		add_action( 'rest_api_init', array( Routes::class, 'register' ) );
		add_action( Installer::CLEANUP_HOOK, array( Installer::class, 'cleanup' ) );
		add_action( 'wp_initialize_site', array( Installer::class, 'initialize_site' ), 20 );
		add_action( 'before_woocommerce_init', array( self::class, 'declare_woocommerce_compatibility' ) );

		if ( is_admin() ) {
			Admin::register();
			SiteHealth::register();
			Privacy::register();
		}
		add_action( 'admin_bar_menu', array( Admin::class, 'admin_bar' ), 80 );
	}

	public static function declare_woocommerce_compatibility(): void {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', MINDIO_MAGIC_AI_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', MINDIO_MAGIC_AI_FILE, true );
		}
	}
}
