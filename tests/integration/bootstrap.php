<?php
/**
 * Shared test harness: loads WordPress, talks to the scripted provider mock, and calls REST routes in process.
 *
 * Run a suite with: WP_PATH=/path/to/wordpress php tests/integration/<suite>.php
 * The provider mock must be running: php -S 127.0.0.1:8766 tests/mock/provider.php
 *
 * @package MindioMagicAI
 */

declare(strict_types=1);

$wp_path = getenv( 'WP_PATH' ) ?: dirname( __DIR__, 3 ) . '/wordpress';
if ( ! defined( 'WP_USE_THEMES' ) ) {
	define( 'WP_USE_THEMES', false );
}
require rtrim( $wp_path, '/\\' ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

if ( ! class_exists( '\MindioMagicAI\Core\Plugin' ) ) {
	fwrite( STDERR, "Activate Mindio Magic AI before running the tests.\n" );
	exit( 1 );
}

const MINDIO_TEST_MOCK = '127.0.0.1:8766';

final class Mindio_Test {
	public static int $assertions = 0;

	/** @var array<int,int> */
	private static array $users = array();

	/** @var array<int,int> */
	private static array $posts = array();

	public static function assert( bool $condition, string $message ): void {
		++self::$assertions;
		if ( ! $condition ) {
			throw new RuntimeException( 'Assertion failed: ' . $message );
		}
	}

	public static function same( mixed $expected, mixed $actual, string $message ): void {
		self::assert( $expected === $actual, $message . ' (expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . ')' );
	}

	public static function mock_dir(): string {
		return getenv( 'MINDIO_MOCK_DIR' ) ?: sys_get_temp_dir() . '/mindio-ai-mock';
	}

	/** @param array<int,array<string,mixed>> $turns */
	public static function script( array $turns ): void {
		if ( ! is_dir( self::mock_dir() ) ) {
			mkdir( self::mock_dir(), 0777, true );
		}
		file_put_contents( self::mock_dir() . '/queue.json', json_encode( $turns ) );
		@unlink( self::mock_dir() . '/requests.jsonl' );
	}

	/** @return array<int,array<string,mixed>> */
	public static function requests(): array {
		$file = self::mock_dir() . '/requests.jsonl';
		if ( ! is_readable( $file ) ) {
			return array();
		}
		return array_map( static fn( string $line ): array => json_decode( $line, true ) ?: array(), array_filter( explode( "\n", (string) file_get_contents( $file ) ) ) );
	}

	public static function user( string $role ): int {
		$login = 'mindio_test_' . $role . '_' . wp_generate_password( 6, false );
		$id    = wp_insert_user(
			array(
				'user_login' => $login,
				'user_pass'  => wp_generate_password( 24 ),
				'user_email' => $login . '@example.test',
				'role'       => $role,
			)
		);
		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( $id->get_error_message() );
		}
		self::$users[] = (int) $id;
		return (int) $id;
	}

	public static function as( int $user_id ): void {
		wp_set_current_user( $user_id );
	}

	/** @param array<string,mixed> $args */
	public static function post( array $args ): int {
		$id = wp_insert_post( $args + array( 'post_status' => 'draft', 'post_type' => 'post' ), true );
		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( $id->get_error_message() );
		}
		self::$posts[] = (int) $id;
		return (int) $id;
	}

	public static function track_post( int $id ): void {
		self::$posts[] = $id;
	}

	/**
	 * @param array<string,mixed>|null $body
	 * @param array<string,mixed>      $query
	 * @return array{status:int,data:mixed}
	 */
	public static function rest( string $method, string $route, ?array $body = null, array $query = array() ): array {
		$request = new WP_REST_Request( $method, '/mindio-magic-ai/v1' . $route );
		if ( null !== $body ) {
			$request->set_header( 'content-type', 'application/json' );
			$request->set_body( (string) wp_json_encode( $body ) );
		}
		foreach ( $query as $key => $value ) {
			$request->set_param( $key, $value );
		}
		$response = rest_do_request( $request );
		$server   = rest_get_server();
		return array(
			'status' => $response->get_status(),
			'data'   => $server->response_to_data( $response, false ),
		);
	}

	public static function reset_plugin_state(): void {
		global $wpdb;
		foreach ( \MindioMagicAI\Core\Installer::TABLES as $table ) {
			$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', \MindioMagicAI\Core\Installer::table( $table ) ) ); // phpcs:ignore
		}
		delete_option( \MindioMagicAI\Core\KillSwitch::OPTION );
		delete_option( \MindioMagicAI\Core\Budget::OPTION );
		delete_option( \MindioMagicAI\Core\Access::OPTION );
		update_option( \MindioMagicAI\Core\Settings::OPTION, \MindioMagicAI\Core\Settings::defaults() );
		\MindioMagicAI\Core\Settings::update( array( 'private_hosts' => array( MINDIO_TEST_MOCK ) ) );
		\MindioMagicAI\Agent\ToolRegistry::reset();
	}

	public static function cleanup(): void {
		foreach ( self::$posts as $id ) {
			wp_delete_post( $id, true );
		}
		foreach ( self::$users as $id ) {
			wp_delete_user( $id );
		}
	}

	/** @param callable():void $suite */
	public static function run( string $name, callable $suite ): void {
		$started = microtime( true );
		try {
			self::reset_plugin_state();
			$suite();
			self::cleanup();
			printf( "OK  %s (%d assertions, %.1fs)\n", $name, self::$assertions, microtime( true ) - $started );
			exit( 0 );
		} catch ( Throwable $e ) {
			self::cleanup();
			fwrite( STDERR, sprintf( "FAIL %s: %s\n  at %s:%d\n", $name, $e->getMessage(), $e->getFile(), $e->getLine() ) );
			exit( 1 );
		}
	}
}
