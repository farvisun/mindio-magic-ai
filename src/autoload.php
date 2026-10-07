<?php
/**
 * Autoloaders for plugin classes and the namespace-prefixed vendor libraries.
 *
 * @package MindioMagicAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'MindioMagicAI\\';
		if ( ! str_starts_with( $class_name, $prefix ) || str_starts_with( $class_name, $prefix . 'Vendor\\' ) ) {
			return;
		}
		$relative = str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) );
		$file     = __DIR__ . '/' . $relative . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

$mindio_magic_ai_vendor = dirname( __DIR__ ) . '/vendor-prefixed/autoload.php';
if ( is_readable( $mindio_magic_ai_vendor ) ) {
	require_once $mindio_magic_ai_vendor;
}
unset( $mindio_magic_ai_vendor );
