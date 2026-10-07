<?php
/**
 * Plugin Name:       Mindio Magic AI
 * Plugin URI:        https://github.com/farvisun/mindio-magic-ai
 * Description:       Bring-your-own-key AI assistant for WordPress and WooCommerce admins, with reviewable changes, undo, budgets, and an audit trail.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            Farvisun
 * Author URI:        https://github.com/farvisun
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mindio-magic-ai
 * Domain Path:       /languages
 * WC requires at least: 8.0
 * WC tested up to:   10.9
 *
 * @package MindioMagicAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MINDIO_MAGIC_AI_VERSION', '0.1.0' );
define( 'MINDIO_MAGIC_AI_DB_VERSION', '1' );
define( 'MINDIO_MAGIC_AI_FILE', __FILE__ );
define( 'MINDIO_MAGIC_AI_DIR', plugin_dir_path( __FILE__ ) );
define( 'MINDIO_MAGIC_AI_URL', plugin_dir_url( __FILE__ ) );

if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Mindio Magic AI requires PHP 8.1 or newer.', 'mindio-magic-ai' ) . '</p></div>';
		}
	);
	return;
}

require_once MINDIO_MAGIC_AI_DIR . 'src/autoload.php';

register_activation_hook( __FILE__, array( \MindioMagicAI\Core\Installer::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \MindioMagicAI\Core\Installer::class, 'deactivate' ) );

add_action( 'plugins_loaded', array( \MindioMagicAI\Core\Plugin::class, 'boot' ) );
