<?php
/**
 * REST API for the admin app. Every route requires a logged-in user, the REST nonce, and a capability.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Rest;

use MindioMagicAI\Agent\ProposalQueue;
use MindioMagicAI\Agent\RunEvents;
use MindioMagicAI\AI\Connections;
use MindioMagicAI\AI\Gateway;
use MindioMagicAI\AI\Presets;
use MindioMagicAI\Assistant\Conversations;
use MindioMagicAI\Assistant\Runs;
use MindioMagicAI\Core\Access;
use MindioMagicAI\Core\AuditLog;
use MindioMagicAI\Core\Budget;
use MindioMagicAI\Core\KillSwitch;
use MindioMagicAI\Core\Pricing;
use MindioMagicAI\Core\RateLimiter;
use MindioMagicAI\Core\SecretBox;
use MindioMagicAI\Core\Settings;
use MindioMagicAI\Core\UsageLedger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Routes {
	public const NAMESPACE = 'mindio-magic-ai/v1';

	public static function register(): void {
		$ns = self::NAMESPACE;

		register_rest_route( $ns, '/bootstrap', array( 'methods' => 'GET', 'callback' => array( self::class, 'bootstrap' ), 'permission_callback' => self::any_cap() ) );

		register_rest_route(
			$ns,
			'/connections',
			array(
				array( 'methods' => 'GET', 'callback' => array( self::class, 'list_connections' ), 'permission_callback' => self::cap( Access::USE_ASSISTANT ) ),
				array( 'methods' => 'POST', 'callback' => array( self::class, 'create_connection' ), 'permission_callback' => array( self::class, 'connections_admin' ) ),
			)
		);
		register_rest_route(
			$ns,
			'/connections/(?P<id>\d+)',
			array(
				array( 'methods' => 'PATCH', 'callback' => array( self::class, 'update_connection' ), 'permission_callback' => array( self::class, 'connections_admin' ) ),
				array( 'methods' => 'DELETE', 'callback' => array( self::class, 'delete_connection' ), 'permission_callback' => array( self::class, 'connections_admin' ) ),
			)
		);
		register_rest_route( $ns, '/connections/(?P<id>\d+)/test', array( 'methods' => 'POST', 'callback' => array( self::class, 'test_connection' ), 'permission_callback' => array( self::class, 'connections_admin' ) ) );
		register_rest_route( $ns, '/connections/(?P<id>\d+)/models', array( 'methods' => 'POST', 'callback' => array( self::class, 'fetch_models' ), 'permission_callback' => array( self::class, 'connections_admin' ) ) );

		register_rest_route(
			$ns,
			'/settings',
			array(
				array( 'methods' => 'GET', 'callback' => array( self::class, 'get_settings' ), 'permission_callback' => self::cap( 'manage_options' ) ),
				array( 'methods' => 'PATCH', 'callback' => array( self::class, 'update_settings' ), 'permission_callback' => self::cap( 'manage_options' ) ),
			)
		);
		register_rest_route(
			$ns,
			'/access',
			array(
				array( 'methods' => 'GET', 'callback' => array( self::class, 'get_access' ), 'permission_callback' => self::cap( 'manage_options' ) ),
				array( 'methods' => 'PUT', 'callback' => array( self::class, 'update_access' ), 'permission_callback' => self::cap( 'manage_options' ) ),
			)
		);
		register_rest_route(
			$ns,
			'/budgets',
			array(
				array( 'methods' => 'GET', 'callback' => array( self::class, 'get_budgets' ), 'permission_callback' => self::cap( Access::MANAGE_SETTINGS ) ),
				array( 'methods' => 'PUT', 'callback' => array( self::class, 'update_budgets' ), 'permission_callback' => self::cap( 'manage_options' ) ),
			)
		);
		register_rest_route(
			$ns,
			'/prices',
			array(
				array( 'methods' => 'GET', 'callback' => array( self::class, 'get_prices' ), 'permission_callback' => self::cap( Access::MANAGE_SETTINGS ) ),
				array( 'methods' => 'PUT', 'callback' => array( self::class, 'update_prices' ), 'permission_callback' => self::cap( 'manage_options' ) ),
			)
		);
		register_rest_route( $ns, '/killswitch', array( 'methods' => 'POST', 'callback' => array( self::class, 'set_killswitch' ), 'permission_callback' => self::cap( 'manage_options' ) ) );
		register_rest_route( $ns, '/keys/acknowledge', array( 'methods' => 'POST', 'callback' => array( self::class, 'acknowledge_keys' ), 'permission_callback' => self::cap( 'manage_options' ) ) );
		register_rest_route( $ns, '/usage', array( 'methods' => 'GET', 'callback' => array( self::class, 'usage' ), 'permission_callback' => self::cap( Access::MANAGE_SETTINGS ) ) );
		register_rest_route( $ns, '/audit', array( 'methods' => 'GET', 'callback' => array( self::class, 'audit' ), 'permission_callback' => self::cap( Access::MANAGE_SETTINGS ) ) );

		register_rest_route(
			$ns,
			'/conversations',
			array(
				array( 'methods' => 'GET', 'callback' => array( self::class, 'list_conversations' ), 'permission_callback' => self::cap( Access::USE_ASSISTANT ) ),
				array( 'methods' => 'POST', 'callback' => array( self::class, 'create_conversation' ), 'permission_callback' => self::cap( Access::USE_ASSISTANT ) ),
			)
		);
		register_rest_route(
			$ns,
			'/conversations/(?P<id>\d+)',
			array(
				array( 'methods' => 'GET', 'callback' => array( self::class, 'get_conversation' ), 'permission_callback' => self::cap( Access::USE_ASSISTANT ) ),
				array( 'methods' => 'PATCH', 'callback' => array( self::class, 'update_conversation' ), 'permission_callback' => self::cap( Access::USE_ASSISTANT ) ),
				array( 'methods' => 'DELETE', 'callback' => array( self::class, 'delete_conversation' ), 'permission_callback' => self::cap( Access::USE_ASSISTANT ) ),
			)
		);
		register_rest_route( $ns, '/conversations/(?P<id>\d+)/fork', array( 'methods' => 'POST', 'callback' => array( self::class, 'fork_conversation' ), 'permission_callback' => self::cap( Access::USE_ASSISTANT ) ) );
		register_rest_route( $ns, '/conversations/(?P<id>\d+)/runs', array( 'methods' => 'POST', 'callback' => array( self::class, 'create_run' ), 'permission_callback' => self::cap( Access::USE_ASSISTANT ) ) );
		register_rest_route( $ns, '/runs/(?P<id>\d+)/execute', array( 'methods' => 'POST', 'callback' => array( self::class, 'execute_run' ), 'permission_callback' => self::cap( Access::USE_ASSISTANT ) ) );
		register_rest_route( $ns, '/runs/(?P<id>\d+)/events', array( 'methods' => 'GET', 'callback' => array( self::class, 'run_events' ), 'permission_callback' => self::cap( Access::USE_ASSISTANT ) ) );
		register_rest_route( $ns, '/runs/(?P<id>\d+)/stop', array( 'methods' => 'POST', 'callback' => array( self::class, 'stop_run' ), 'permission_callback' => self::cap( Access::USE_ASSISTANT ) ) );
		register_rest_route( $ns, '/proposals/(?P<id>pr_[a-f0-9]{24})/apply', array( 'methods' => 'POST', 'callback' => array( self::class, 'apply_proposal' ), 'permission_callback' => self::cap( Access::USE_ASSISTANT ) ) );
		register_rest_route( $ns, '/proposals/(?P<id>pr_[a-f0-9]{24})/reject', array( 'methods' => 'POST', 'callback' => array( self::class, 'reject_proposal' ), 'permission_callback' => self::cap( Access::USE_ASSISTANT ) ) );
		register_rest_route( $ns, '/proposals/(?P<id>pr_[a-f0-9]{24})/undo', array( 'methods' => 'POST', 'callback' => array( self::class, 'undo_proposal' ), 'permission_callback' => self::cap( Access::USE_ASSISTANT ) ) );
		register_rest_route( $ns, '/preferences', array( 'methods' => 'POST', 'callback' => array( self::class, 'preferences' ), 'permission_callback' => self::cap( Access::USE_ASSISTANT ) ) );
	}

	private static function cap( string $capability ): \Closure {
		return static fn(): bool => is_user_logged_in() && current_user_can( $capability );
	}

	private static function any_cap(): \Closure {
		return static function (): bool {
			if ( ! is_user_logged_in() ) {
				return false;
			}
			foreach ( Access::capabilities() as $cap ) {
				if ( current_user_can( $cap ) ) {
					return true;
				}
			}
			return false;
		};
	}

	public static function connections_admin(): bool {
		return is_user_logged_in() && Access::can_manage_connections();
	}

	/* ---------- Bootstrap ---------- */

	public static function bootstrap(): \WP_REST_Response {
		$caps = array();
		foreach ( array_merge( Access::capabilities(), array( 'manage_options' ) ) as $cap ) {
			$caps[ $cap ] = current_user_can( $cap );
		}
		$connections = new Connections();
		$settings    = Settings::all();

		return rest_ensure_response(
			array(
				'version'      => MINDIO_MAGIC_AI_VERSION,
				'caps'         => $caps,
				'manage_connections' => Access::can_manage_connections(),
				'kill_switch'  => array(
					'active' => KillSwitch::active(),
					'locked' => KillSwitch::locked_by_constant(),
				),
				'keys_current' => SecretBox::key_is_current(),
				'encryption'   => SecretBox::available(),
				'presets'      => self::presets(),
				'connections'  => array_map( array( $connections, 'to_public' ), $connections->all() ),
				'assistant'    => array(
					'connection' => (int) $settings['assistant_connection'],
					'model'      => (string) $settings['assistant_model'],
					'auto_apply_allowed' => (bool) $settings['allow_auto_apply'],
					'auto_apply' => (bool) get_user_meta( get_current_user_id(), Runs::USER_AUTO_APPLY, true ),
					'transport'  => (string) $settings['stream_transport'],
				),
				'woocommerce'  => class_exists( 'WooCommerce' ),
				'currency'     => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
				'site'         => array(
					'name'     => html_entity_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ),
					'timezone' => wp_timezone_string(),
					'locale'   => get_user_locale(),
				),
			)
		);
	}

	/** @return array<int,array<string,mixed>> */
	private static function presets(): array {
		$out = array();
		foreach ( Presets::all() as $id => $preset ) {
			$out[] = array(
				'id'            => $id,
				'label'         => $preset['label'],
				'provider'      => $preset['provider'],
				'base_url'      => $preset['base_url'],
				'editable_url'  => $preset['editable_url'],
				'default_model' => $preset['default_model'],
				'fast_model'    => $preset['fast_model'],
				'suggested'     => $preset['suggested'],
				'keys_url'      => $preset['keys_url'],
				'constant'      => $preset['constant'],
				'constant_set'  => '' !== $preset['constant'] && defined( $preset['constant'] ),
			);
		}
		return $out;
	}

	/* ---------- Connections ---------- */

	public static function list_connections(): \WP_REST_Response {
		$connections = new Connections();
		return rest_ensure_response( array_map( array( $connections, 'to_public' ), $connections->all() ) );
	}

	public static function create_connection( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		if ( ! SecretBox::available() ) {
			return new \WP_Error( 'no_encryption', __( 'This server cannot encrypt keys (needs the sodium or OpenSSL PHP extension).', 'mindio-magic-ai' ), array( 'status' => 500 ) );
		}
		$connections = new Connections();
		$created     = $connections->create( (array) $request->get_json_params() );
		if ( is_wp_error( $created ) ) {
			return $created;
		}
		return rest_ensure_response( $connections->to_public( $created ) );
	}

	public static function update_connection( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$connections = new Connections();
		$updated     = $connections->update( (int) $request['id'], (array) $request->get_json_params() );
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}
		return rest_ensure_response( $connections->to_public( $updated ) );
	}

	public static function delete_connection( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		if ( ! ( new Connections() )->delete( (int) $request['id'] ) ) {
			return new \WP_Error( 'not_found', __( 'Connection not found.', 'mindio-magic-ai' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( array( 'deleted' => true ) );
	}

	public static function test_connection( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$limited = self::limit( 'connection-test', 20, 300 );
		if ( $limited ) {
			return $limited;
		}
		$connections = new Connections();
		$connection  = $connections->get( (int) $request['id'] );
		if ( ! $connection ) {
			return new \WP_Error( 'not_found', __( 'Connection not found.', 'mindio-magic-ai' ), array( 'status' => 404 ) );
		}
		$started = microtime( true );
		$models  = Gateway::provider( $connection )->list_models();
		$ms      = (int) round( ( microtime( true ) - $started ) * 1000 );
		AuditLog::write( 'connection.test', 'connection', (string) $connection->id, array( 'ok' => ! is_wp_error( $models ), 'ms' => $ms ), ! is_wp_error( $models ) );
		if ( is_wp_error( $models ) ) {
			return $models;
		}
		$connections->cache_models( $connection, $models );
		$default = $connection->default_model();
		return rest_ensure_response(
			array(
				'ok'            => true,
				'latency_ms'    => $ms,
				'models'        => count( $models ),
				'model_found'   => '' === $default || ! $models || in_array( $default, $models, true ),
				'connection'    => $connections->to_public( (new Connections())->get( $connection->id ) ?? $connection ),
			)
		);
	}

	public static function fetch_models( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$limited = self::limit( 'connection-models', 20, 300 );
		if ( $limited ) {
			return $limited;
		}
		$connections = new Connections();
		$connection  = $connections->get( (int) $request['id'] );
		if ( ! $connection ) {
			return new \WP_Error( 'not_found', __( 'Connection not found.', 'mindio-magic-ai' ), array( 'status' => 404 ) );
		}
		$models = Gateway::provider( $connection )->list_models();
		if ( is_wp_error( $models ) ) {
			return $models;
		}
		$connections->cache_models( $connection, $models );
		return rest_ensure_response( array( 'models' => $models ) );
	}

	/* ---------- Settings, access, budgets ---------- */

	public static function get_settings(): \WP_REST_Response {
		$roles = array();
		foreach ( wp_roles()->roles as $id => $role ) {
			$roles[] = array(
				'id'   => $id,
				'name' => translate_user_role( (string) $role['name'] ),
			);
		}
		return rest_ensure_response(
			array(
				'settings'       => Settings::all(),
				'dedicated_key'  => SecretBox::uses_dedicated_key(),
				'proxy'          => \MindioMagicAI\Core\UrlGuard::proxy_active(),
			)
		);
	}

	public static function update_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$input  = (array) $request->get_json_params();
		$before = Settings::all();
		$after  = Settings::update( $input );
		$diff   = array();
		foreach ( $after as $key => $value ) {
			if ( $before[ $key ] !== $value ) {
				$diff[] = $key;
			}
		}
		AuditLog::write( 'settings.update', 'settings', '', array( 'fields' => $diff ) );
		return rest_ensure_response( array( 'settings' => $after ) );
	}

	public static function get_access(): \WP_REST_Response {
		$roles = array();
		foreach ( wp_roles()->roles as $id => $role ) {
			$roles[] = array(
				'id'     => $id,
				'name'   => translate_user_role( (string) $role['name'] ),
				'locked' => 'administrator' === $id,
			);
		}
		return rest_ensure_response(
			array(
				'roles'        => $roles,
				'capabilities' => Access::capability_labels(),
				'map'          => (object) Access::map(),
			)
		);
	}

	public static function update_access( \WP_REST_Request $request ): \WP_REST_Response {
		$map = Access::update( (array) ( $request->get_json_params()['map'] ?? array() ) );
		AuditLog::write( 'access.update', 'access', '', array( 'roles' => array_keys( $map ) ) );
		return rest_ensure_response( array( 'map' => (object) $map ) );
	}

	public static function get_budgets(): \WP_REST_Response {
		return rest_ensure_response(
			array(
				'budgets'     => Budget::all(),
				'consumption' => Budget::consumption(),
			)
		);
	}

	public static function update_budgets( \WP_REST_Request $request ): \WP_REST_Response {
		$budgets = Budget::update( (array) ( $request->get_json_params()['budgets'] ?? array() ) );
		AuditLog::write( 'budgets.update', 'budgets', '', $budgets );
		return rest_ensure_response(
			array(
				'budgets'     => $budgets,
				'consumption' => Budget::consumption(),
			)
		);
	}

	public static function get_prices(): \WP_REST_Response {
		return rest_ensure_response( array( 'prices' => (object) Pricing::all() ) );
	}

	public static function update_prices( \WP_REST_Request $request ): \WP_REST_Response {
		$prices = Pricing::update( (array) ( $request->get_json_params()['prices'] ?? array() ) );
		AuditLog::write( 'prices.update', 'prices', '', array( 'models' => array_keys( $prices ) ) );
		return rest_ensure_response( array( 'prices' => (object) $prices ) );
	}

	public static function set_killswitch( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		if ( KillSwitch::locked_by_constant() ) {
			return new \WP_Error( 'locked', __( 'AI is paused by the MINDIO_MAGIC_AI_DISABLED constant in wp-config.php.', 'mindio-magic-ai' ), array( 'status' => 409 ) );
		}
		KillSwitch::set( rest_sanitize_boolean( $request->get_json_params()['active'] ?? false ) );
		return rest_ensure_response( array( 'active' => KillSwitch::active() ) );
	}

	public static function acknowledge_keys(): \WP_REST_Response {
		SecretBox::reset_key_check();
		AuditLog::write( 'keys.acknowledge', 'keys', '' );
		return rest_ensure_response( array( 'keys_current' => true ) );
	}

	public static function usage( \WP_REST_Request $request ): \WP_REST_Response {
		return rest_ensure_response(
			UsageLedger::report( (int) ( $request->get_param( 'days' ) ?: 30 ) ) + array(
				'budgets'     => Budget::all(),
				'consumption' => Budget::consumption(),
				'kill_switch' => KillSwitch::active(),
			)
		);
	}

	public static function audit( \WP_REST_Request $request ): \WP_REST_Response {
		return rest_ensure_response( AuditLog::query( $request->get_params() ) );
	}

	/* ---------- Conversations and runs ---------- */

	/** @return array<string,mixed>|\WP_Error */
	private static function own_conversation( int $id ): array|\WP_Error {
		$conversation = ( new Conversations() )->get( $id );
		if ( ! $conversation || $conversation['user_id'] !== get_current_user_id() ) {
			return new \WP_Error( 'not_found', __( 'Conversation not found.', 'mindio-magic-ai' ), array( 'status' => 404 ) );
		}
		return $conversation;
	}

	/** @return array<string,mixed>|\WP_Error */
	private static function own_run( int $id ): array|\WP_Error {
		$run = ( new Runs() )->get( $id );
		if ( ! $run || $run['user_id'] !== get_current_user_id() ) {
			return new \WP_Error( 'not_found', __( 'Run not found.', 'mindio-magic-ai' ), array( 'status' => 404 ) );
		}
		return $run;
	}

	public static function list_conversations( \WP_REST_Request $request ): \WP_REST_Response {
		return rest_ensure_response(
			( new Conversations() )->list(
				get_current_user_id(),
				sanitize_text_field( (string) $request->get_param( 'search' ) ),
				rest_sanitize_boolean( $request->get_param( 'archived' ) ?? false )
			)
		);
	}

	public static function create_conversation( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$params      = (array) $request->get_json_params();
		$connections = new Connections();
		$connection  = ! empty( $params['connection_id'] ) ? $connections->get( (int) $params['connection_id'] ) : $connections->for_assistant();
		if ( ! $connection || ! $connection->has_key() ) {
			return new \WP_Error( 'no_connection', __( 'Add a provider connection with an API key before starting a conversation.', 'mindio-magic-ai' ), array( 'status' => 400 ) );
		}
		$model = Settings::model_id( $params['model'] ?? '' );
		if ( '' === $model ) {
			$configured = (string) Settings::get( 'assistant_model' );
			$model      = '' !== $configured && (int) Settings::get( 'assistant_connection' ) === $connection->id ? $configured : $connection->default_model();
		}
		if ( '' === $model ) {
			return new \WP_Error( 'no_model', __( 'Choose a default model for this connection first.', 'mindio-magic-ai' ), array( 'status' => 400 ) );
		}
		$conversations = new Conversations();
		$id            = $conversations->create( get_current_user_id(), $connection->id, $model );
		return rest_ensure_response( $conversations->get( $id ) );
	}

	public static function get_conversation( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$conversation = self::own_conversation( (int) $request['id'] );
		if ( is_wp_error( $conversation ) ) {
			return $conversation;
		}
		$conversations = new Conversations();
		$runs          = new Runs();
		$active        = $runs->active_run( $conversation['id'] );
		$connection    = ( new Connections() )->get( $conversation['connection_id'] );
		return rest_ensure_response(
			$conversation + array(
				'connection_name' => $connection ? $connection->name : '',
				'messages'        => $conversations->to_public( $conversations->messages( $conversation['id'] ) ),
				'proposals'       => ProposalQueue::for_conversation( $conversation['id'] ),
				'active_run'      => $active ? $runs->get( $active ) : null,
			)
		);
	}

	public static function update_conversation( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$conversation = self::own_conversation( (int) $request['id'] );
		if ( is_wp_error( $conversation ) ) {
			return $conversation;
		}
		$conversations = new Conversations();
		$conversations->update( $conversation['id'], (array) $request->get_json_params() );
		return rest_ensure_response( $conversations->get( $conversation['id'] ) );
	}

	public static function delete_conversation( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$conversation = self::own_conversation( (int) $request['id'] );
		if ( is_wp_error( $conversation ) ) {
			return $conversation;
		}
		( new Conversations() )->delete( $conversation['id'] );
		return rest_ensure_response( array( 'deleted' => true ) );
	}

	public static function fork_conversation( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$conversation = self::own_conversation( (int) $request['id'] );
		if ( is_wp_error( $conversation ) ) {
			return $conversation;
		}
		$conversations = new Conversations();
		$new_id        = $conversations->fork( $conversation['id'], get_current_user_id(), absint( $request->get_json_params()['before_message_id'] ?? 0 ) );
		return rest_ensure_response( $conversations->get( $new_id ) );
	}

	public static function create_run( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$limited = self::limit( 'assistant-run', 30, 60 );
		if ( $limited ) {
			return $limited;
		}
		if ( KillSwitch::active() ) {
			return new \WP_Error( 'kill_switch', __( 'AI features are paused by an administrator.', 'mindio-magic-ai' ), array( 'status' => 503 ) );
		}
		$params = (array) $request->get_json_params();
		$run    = ( new Runs() )->create(
			(int) $request['id'],
			get_current_user_id(),
			(string) ( $params['message'] ?? '' ),
			is_array( $params['images'] ?? null ) ? $params['images'] : array(),
			is_array( $params['screen'] ?? null ) ? $params['screen'] : array()
		);
		return is_wp_error( $run ) ? $run : rest_ensure_response( $run );
	}

	public static function execute_run( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		// WordPress applies ?_locale=user only to JSON requests; a stream asks for text/event-stream.
		switch_to_user_locale( get_current_user_id() );
		$run = self::own_run( (int) $request['id'] );
		if ( is_wp_error( $run ) ) {
			return $run;
		}
		if ( Runs::QUEUED !== $run['status'] ) {
			return new \WP_Error( 'not_queued', __( 'This run has already started.', 'mindio-magic-ai' ), array( 'status' => 409 ) );
		}

		$runs = new Runs();
		if ( str_contains( (string) $request->get_header( 'accept' ), 'text/event-stream' ) && 'poll' !== Settings::get( 'stream_transport' ) ) {
			Stream::open();
			$runs->execute( $run['id'], array( Stream::class, 'write' ) );
			Stream::close();
		}

		ignore_user_abort( true );
		$runs->execute( $run['id'] );
		return rest_ensure_response( $runs->get( $run['id'] ) );
	}

	public static function run_events( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$run = self::own_run( (int) $request['id'] );
		if ( is_wp_error( $run ) ) {
			return $run;
		}
		$response = rest_ensure_response(
			array(
				'run'    => $run,
				'events' => RunEvents::since( $run['id'], absint( $request->get_param( 'after' ) ) ),
			)
		);
		$response->header( 'Cache-Control', 'no-store' );
		return $response;
	}

	public static function stop_run( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$run = self::own_run( (int) $request['id'] );
		if ( is_wp_error( $run ) ) {
			return $run;
		}
		( new Runs() )->stop( $run['id'] );
		return rest_ensure_response( ( new Runs() )->get( $run['id'] ) );
	}

	public static function apply_proposal( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$result = ProposalQueue::apply( (string) $request['id'], get_current_user_id() );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public static function reject_proposal( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$result = ProposalQueue::reject( (string) $request['id'], get_current_user_id() );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public static function undo_proposal( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$result = ProposalQueue::undo( (string) $request['id'], get_current_user_id(), rest_sanitize_boolean( $request->get_json_params()['force'] ?? false ) );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public static function preferences( \WP_REST_Request $request ): \WP_REST_Response {
		$params = (array) $request->get_json_params();
		if ( array_key_exists( 'auto_apply', $params ) ) {
			update_user_meta( get_current_user_id(), Runs::USER_AUTO_APPLY, rest_sanitize_boolean( $params['auto_apply'] ) ? 1 : 0 );
		}
		return rest_ensure_response( array( 'auto_apply' => (bool) get_user_meta( get_current_user_id(), Runs::USER_AUTO_APPLY, true ) ) );
	}

	private static function limit( string $bucket, int $limit, int $window ): ?\WP_Error {
		$result = RateLimiter::consume( $bucket, (string) get_current_user_id(), $limit, $window );
		if ( $result['allowed'] ) {
			return null;
		}
		return new \WP_Error(
			'rate_limited',
			__( 'Too many requests. Wait a moment and try again.', 'mindio-magic-ai' ),
			array(
				'status'      => 429,
				'retry_after' => $result['retry_after'],
			)
		);
	}
}
