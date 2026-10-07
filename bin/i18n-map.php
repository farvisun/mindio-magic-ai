<?php
/**
 * Prints the source-to-bundle map that `wp i18n make-json --use-map` needs, so script
 * translations are keyed by the built file WordPress actually loads.
 *
 * Usage: php bin/i18n-map.php > build/i18n-map.json
 *
 * @package MindioMagicAI
 */

$root = dirname( __DIR__ );
$map  = array();
foreach ( array( 'admin', 'drawer' ) as $entry ) {
	$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( "$root/assets/src/$entry", FilesystemIterator::SKIP_DOTS ) );
	foreach ( $files as $file ) {
		if ( 'js' === $file->getExtension() && ! str_ends_with( $file->getFilename(), '.test.js' ) ) {
			$map[ substr( $file->getPathname(), strlen( $root ) + 1 ) ] = "build/$entry.js";
		}
	}
}
ksort( $map );
echo json_encode( $map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), "\n";
