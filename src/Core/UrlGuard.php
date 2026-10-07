<?php
/**
 * Outbound URL validation with DNS pinning.
 *
 * Every outbound request resolves its host once here; the transport then pins
 * cURL to the validated addresses so a second DNS answer cannot redirect the
 * request to a private network (DNS rebinding).
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UrlGuard {
	/** Ranges that are never reachable from the public internet. */
	private const DENY_V4 = array(
		'0.0.0.0/8',
		'10.0.0.0/8',
		'100.64.0.0/10',
		'127.0.0.0/8',
		'169.254.0.0/16',
		'172.16.0.0/12',
		'192.0.0.0/24',
		'192.0.2.0/24',
		'192.88.99.0/24',
		'192.168.0.0/16',
		'198.18.0.0/15',
		'198.51.100.0/24',
		'203.0.113.0/24',
		'224.0.0.0/4',
		'240.0.0.0/4',
	);

	private const DENY_V6 = array(
		'::/128',
		'::1/128',
		'::ffff:0:0/96',
		'64:ff9b::/96',
		'64:ff9b:1::/48',
		'100::/64',
		'2001:db8::/32',
		'2002::/16',
		'fc00::/7',
		'fe80::/10',
		'ff00::/8',
	);

	/**
	 * Validate a URL and resolve its host.
	 *
	 * @param array<int,string> $private_hosts Exact "host:port" pairs allowed to resolve to private addresses (and to use HTTP).
	 * @return array{url:string,scheme:string,host:string,port:int,ips:array<int,string>,private:bool}|\WP_Error
	 */
	public static function check( string $url, array $private_hosts = array() ): array|\WP_Error {
		$url   = trim( $url );
		$parts = wp_parse_url( $url );
		if ( '' === $url || ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) ) {
			return new \WP_Error( 'invalid_url', __( 'The URL is invalid.', 'mindio-magic-ai' ) );
		}

		$scheme = strtolower( (string) $parts['scheme'] );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return new \WP_Error( 'invalid_url', __( 'Only HTTP and HTTPS URLs are allowed.', 'mindio-magic-ai' ) );
		}
		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return new \WP_Error( 'url_credentials_forbidden', __( 'URLs containing credentials are not allowed.', 'mindio-magic-ai' ) );
		}

		$host = strtolower( rtrim( trim( (string) $parts['host'], '[]' ), '.' ) );
		$port = isset( $parts['port'] ) ? (int) $parts['port'] : ( 'https' === $scheme ? 443 : 80 );
		$pair = ( str_contains( $host, ':' ) ? '[' . $host . ']' : $host ) . ':' . $port;

		$allowlisted = in_array( $pair, $private_hosts, true );
		if ( 'https' !== $scheme && ! $allowlisted ) {
			return new \WP_Error( 'https_required', __( 'Only HTTPS URLs are allowed. Add the host and port to the private hosts list to use HTTP on a trusted network.', 'mindio-magic-ai' ) );
		}

		$ips = self::resolve( $host );
		if ( ! $ips ) {
			return new \WP_Error( 'unresolvable_url', __( 'The URL host could not be resolved.', 'mindio-magic-ai' ) );
		}

		$private = false;
		foreach ( $ips as $ip ) {
			if ( ! self::is_public_ip( $ip ) ) {
				$private = true;
			}
		}
		if ( $private && ! $allowlisted ) {
			return new \WP_Error( 'private_url', __( 'Local and private network addresses are blocked. An administrator can allow a specific host and port in Settings.', 'mindio-magic-ai' ) );
		}

		return array(
			'url'     => $url,
			'scheme'  => $scheme,
			'host'    => $host,
			'port'    => $port,
			'ips'     => $ips,
			'private' => $private,
		);
	}

	/** @return array<int,string> */
	public static function resolve( string $host ): array {
		if ( false !== filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return array( $host );
		}
		if ( 'localhost' === $host || str_ends_with( $host, '.localhost' ) ) {
			return array( '127.0.0.1' );
		}

		$ips = array();
		if ( function_exists( 'dns_get_record' ) ) {
			$records = @dns_get_record( $host, DNS_A | DNS_AAAA ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- A DNS failure becomes a validation error.
			foreach ( (array) $records as $record ) {
				if ( ! empty( $record['ip'] ) ) {
					$ips[] = (string) $record['ip'];
				}
				if ( ! empty( $record['ipv6'] ) ) {
					$ips[] = (string) $record['ipv6'];
				}
			}
		}
		if ( ! $ips && function_exists( 'gethostbynamel' ) ) {
			$ips = (array) gethostbynamel( $host );
		}

		return array_values( array_unique( array_filter( array_map( 'strval', $ips ), static fn( string $ip ): bool => false !== filter_var( $ip, FILTER_VALIDATE_IP ) ) ) );
	}

	public static function is_public_ip( string $ip ): bool {
		if ( false === filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return false;
		}
		$flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
		if ( defined( 'FILTER_FLAG_GLOBAL_RANGE' ) ) {
			$flags |= FILTER_FLAG_GLOBAL_RANGE;
		}
		if ( false === filter_var( $ip, FILTER_VALIDATE_IP, $flags ) ) {
			return false;
		}

		$is_v6 = str_contains( $ip, ':' );
		foreach ( $is_v6 ? self::DENY_V6 : self::DENY_V4 as $cidr ) {
			if ( self::in_cidr( $ip, $cidr ) ) {
				return false;
			}
		}
		return true;
	}

	public static function in_cidr( string $ip, string $cidr ): bool {
		[ $subnet, $bits ] = explode( '/', $cidr ) + array( 1 => '' );
		$ip_bin            = @inet_pton( $ip ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Invalid input returns false.
		$subnet_bin        = @inet_pton( $subnet ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Invalid input returns false.
		if ( false === $ip_bin || false === $subnet_bin || strlen( $ip_bin ) !== strlen( $subnet_bin ) ) {
			return false;
		}
		$bits  = '' === $bits ? strlen( $ip_bin ) * 8 : (int) $bits;
		$bytes = intdiv( $bits, 8 );
		if ( substr( $ip_bin, 0, $bytes ) !== substr( $subnet_bin, 0, $bytes ) ) {
			return false;
		}
		$remainder = $bits % 8;
		if ( 0 === $remainder ) {
			return true;
		}
		$mask = chr( ( 0xff << ( 8 - $remainder ) ) & 0xff );
		return ( $ip_bin[ $bytes ] & $mask ) === ( $subnet_bin[ $bytes ] & $mask );
	}

	/**
	 * cURL resolve entries that pin the validated addresses.
	 *
	 * @param array{host:string,port:int,ips:array<int,string>} $checked
	 * @return array<int,string>
	 */
	public static function curl_resolve( array $checked ): array {
		if ( false !== filter_var( $checked['host'], FILTER_VALIDATE_IP ) ) {
			return array();
		}
		$ips = array_map( static fn( string $ip ): string => str_contains( $ip, ':' ) ? '[' . $ip . ']' : $ip, $checked['ips'] );
		return array( $checked['host'] . ':' . $checked['port'] . ':' . implode( ',', $ips ) );
	}

	/**
	 * When WordPress routes requests through a proxy, the proxy resolves hosts and pinning has no effect.
	 */
	public static function proxy_active(): bool {
		return defined( 'WP_PROXY_HOST' ) && '' !== (string) constant( 'WP_PROXY_HOST' );
	}
}
