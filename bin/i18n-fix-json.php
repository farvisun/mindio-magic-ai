<?php
/**
 * Renames script translation files that `wp i18n make-json` keyed by a mangled path.
 *
 * WP-CLI strips ".min.js" with an unescaped pattern, so "build/admin.js" becomes "build/a.js"
 * and WordPress never finds the file. This restores the real path and the matching file name.
 *
 * Usage: php bin/i18n-fix-json.php
 *
 * @package MindioMagicAI
 */

$root    = dirname( __DIR__ );
$scripts = array( 'build/admin.js', 'build/drawer.js' );

foreach ( glob( "$root/languages/*.json" ) ?: array() as $path ) {
	$data = json_decode( (string) file_get_contents( $path ), true );
	if ( ! is_array( $data ) || empty( $data['source'] ) || in_array( $data['source'], $scripts, true ) ) {
		continue;
	}
	foreach ( $scripts as $script ) {
		if ( preg_replace( '/.min.js$/', '.js', $script ) !== $data['source'] ) {
			continue;
		}
		$data['source'] = $script;
		$target         = preg_replace( '/-[0-9a-f]{32}\.json$/', '-' . md5( $script ) . '.json', $path );
		file_put_contents( $target, json_encode( $data, JSON_UNESCAPED_SLASHES ) );
		if ( $target !== $path ) {
			unlink( $path );
		}
		echo 'Fixed ', basename( $target ), "\n";
	}
}
