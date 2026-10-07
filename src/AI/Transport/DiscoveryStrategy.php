<?php
/**
 * Points the prefixed php-http/discovery at this plugin's client and factories.
 *
 * The SDK runs discovery in its constructor even when a client is injected.
 * Registering this strategy first means discovery never picks up a PSR-18
 * client bundled by another plugin (which would implement the unprefixed
 * interfaces and fail the SDK's type checks).
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI\Transport;

use MindioMagicAI\Vendor\Http\Discovery\Psr17FactoryDiscovery;
use MindioMagicAI\Vendor\Http\Discovery\Psr18ClientDiscovery;
use MindioMagicAI\Vendor\Http\Discovery\Strategy\DiscoveryStrategy as DiscoveryStrategyInterface;
use MindioMagicAI\Vendor\Nyholm\Psr7\Factory\Psr17Factory;
use MindioMagicAI\Vendor\Psr\Http\Client\ClientInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DiscoveryStrategy implements DiscoveryStrategyInterface {
	private static bool $registered = false;

	public static function register(): void {
		if ( self::$registered ) {
			return;
		}
		Psr18ClientDiscovery::prependStrategy( self::class );
		Psr17FactoryDiscovery::prependStrategy( self::class );
		self::$registered = true;
	}

	/**
	 * @return array<int,array{class:string|callable}>
	 */
	public static function getCandidates( $type ) {
		if ( ClientInterface::class === $type ) {
			return array( array( 'class' => Psr18Client::class ) );
		}
		if ( str_starts_with( (string) $type, 'MindioMagicAI\\Vendor\\Psr\\Http\\Message\\' ) ) {
			return array( array( 'class' => Psr17Factory::class ) );
		}
		return array();
	}
}
