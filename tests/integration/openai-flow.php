<?php
/**
 * OpenAI-compatible provider: streaming, tool calls, history replay, usage estimation, and errors.
 *
 * @package MindioMagicAI
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

Mindio_Test::run(
	'openai-flow',
	static function (): void {
		$admin = Mindio_Test::user( 'administrator' );
		Mindio_Test::as( $admin );

		$connection = Mindio_Test::rest(
			'POST',
			'/connections',
			array(
				'preset'   => 'custom',
				'name'     => 'Mock gateway',
				'base_url' => 'http://' . MINDIO_TEST_MOCK . '/openai/v1/',
				'api_key'  => 'sk-test-good-oai',
				'models'   => array( 'default' => 'gpt-test' ),
			)
		)['data'];
		Mindio_Test::same( 'http://' . MINDIO_TEST_MOCK . '/openai/v1', $connection['base_url'], 'base URL is normalized' );
		Mindio_Test::same( 'active', $connection['status'], 'connection is active' );

		$models = Mindio_Test::rest( 'POST', '/connections/' . $connection['id'] . '/models' );
		Mindio_Test::same( array( 'gpt-test', 'gpt-test-mini' ), $models['data']['models'], 'models are fetched' );

		$post_id = Mindio_Test::post( array( 'post_title' => 'Gateway post', 'post_content' => 'Hello' ) );
		$conv    = Mindio_Test::rest( 'POST', '/conversations', array( 'connection_id' => $connection['id'] ) )['data'];
		Mindio_Test::script(
			array(
				array( 'tool_calls' => array( array( 'name' => 'get_content', 'input' => array( 'id' => $post_id ) ) ) ),
				array(
					'text'       => 'سلام! عنوان را بهتر می‌کنم.',
					'tool_calls' => array( array( 'name' => 'update_content', 'input' => array( 'id' => $post_id, 'title' => 'عنوان بهتر' ) ) ),
				),
				array(
					'text'       => 'پیشنهاد آماده است.',
					'omit_usage' => true,
				),
			)
		);
		$run  = Mindio_Test::rest( 'POST', '/conversations/' . $conv['id'] . '/runs', array( 'message' => 'عنوان این نوشته را بهتر کن' ) )['data'];
		$done = Mindio_Test::rest( 'POST', '/runs/' . $run['id'] . '/execute' )['data'];
		Mindio_Test::same( 'completed', $done['status'], 'run completes' );

		$calls = array_values( array_filter( Mindio_Test::requests(), static fn( array $r ): bool => '/openai/v1/chat/completions' === $r['path'] ) );
		Mindio_Test::same( 3, count( $calls ), 'three completions' );
		$first = json_decode( $calls[0]['raw'], true );
		Mindio_Test::same( 'system', $first['messages'][0]['role'], 'system message first' );
		Mindio_Test::assert( str_contains( $first['messages'][0]['content'], '<context>' ), 'context is appended to the system message' );
		Mindio_Test::same( 'function', $first['tools'][0]['type'], 'tools use the function format' );
		Mindio_Test::same( true, $first['stream_options']['include_usage'] ?? null, 'usage is requested in the stream' );
		Mindio_Test::same( 'Bearer sk-test-good-oai', $calls[0]['headers']['authorization'] ?? '', 'bearer key' );
		$third = json_decode( $calls[2]['raw'], true );
		$roles = array_column( $third['messages'], 'role' );
		Mindio_Test::same( array( 'system', 'user', 'assistant', 'tool', 'assistant', 'tool' ), $roles, 'history alternates correctly' );
		Mindio_Test::assert( ! empty( $third['messages'][4]['tool_calls'][0]['id'] ), 'assistant tool calls are replayed' );
		Mindio_Test::same( $third['messages'][4]['tool_calls'][0]['id'], $third['messages'][5]['tool_call_id'], 'tool results reference their call' );

		$detail = Mindio_Test::rest( 'GET', '/conversations/' . $conv['id'] )['data'];
		$texts  = array();
		foreach ( $detail['messages'] as $message ) {
			foreach ( $message['content'] as $block ) {
				if ( 'text' === $block['type'] && 'assistant' === $message['role'] ) {
					$texts[] = $block['text'];
				}
			}
		}
		Mindio_Test::same( 'پیشنهاد آماده است.', end( $texts ), 'Persian text survives streaming' );
		Mindio_Test::assert( ! empty( $detail['proposals'] ), 'proposal exists: ' . wp_json_encode( $third['messages'][5] ?? null, JSON_UNESCAPED_UNICODE ) );
		Mindio_Test::same( 'عنوان بهتر', $detail['proposals'][0]['preview']['changes'][0]['after'], 'Persian tool input is parsed' );

		global $wpdb;
		$estimated = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE estimated = 1', \MindioMagicAI\Core\Installer::table( 'usage' ) ) );
		Mindio_Test::same( 1, $estimated, 'missing usage is estimated and flagged' );

		// Errors map to clear codes.
		Mindio_Test::script( array( array( 'status' => 429, 'body' => array( 'error' => array( 'message' => 'quota exceeded' ) ) ) ) );
		$run  = Mindio_Test::rest( 'POST', '/conversations/' . $conv['id'] . '/runs', array( 'message' => 'again' ) )['data'];
		$fail = Mindio_Test::rest( 'POST', '/runs/' . $run['id'] . '/execute' )['data'];
		Mindio_Test::same( 'provider_rate_limited', $fail['error_code'], 'rate limit error code' );
		Mindio_Test::assert( str_contains( $fail['error_message'], 'quota exceeded' ), 'provider detail is shown' );

		// A conversation can continue after an error, and editing an earlier turn forks.
		Mindio_Test::script( array( array( 'text' => 'Recovered.' ) ) );
		$run = Mindio_Test::rest( 'POST', '/conversations/' . $conv['id'] . '/runs', array( 'message' => 'retry' ) )['data'];
		Mindio_Test::same( 'completed', Mindio_Test::rest( 'POST', '/runs/' . $run['id'] . '/execute' )['data']['status'], 'conversation recovers' );
		$first_user = $detail['messages'][0]['id'];
		$fork       = Mindio_Test::rest( 'POST', '/conversations/' . $conv['id'] . '/fork', array( 'before_message_id' => $first_user ) )['data'];
		Mindio_Test::same( $conv['id'], $fork['forked_from'], 'fork records its source' );
		Mindio_Test::same( array(), Mindio_Test::rest( 'GET', '/conversations/' . $fork['id'] )['data']['messages'], 'fork before the first message is empty' );
		$original = Mindio_Test::rest( 'GET', '/conversations/' . $conv['id'] )['data']['messages'];
		Mindio_Test::assert( count( $original ) >= 8, 'original conversation is untouched' );

		// Stop: a stopping run ends without further steps.
		Mindio_Test::script(
			array(
				array( 'tool_calls' => array( array( 'name' => 'site_overview', 'input' => array() ) ) ),
				array( 'text' => 'never' ),
			)
		);
		$run = Mindio_Test::rest( 'POST', '/conversations/' . $conv['id'] . '/runs', array( 'message' => 'overview' ) )['data'];
		Mindio_Test::assert( ( new \MindioMagicAI\Assistant\Runs() )->stop( (int) $run['id'] ), 'queued run can be stopped' );
		$state = ( new \MindioMagicAI\Assistant\Runs() )->get( (int) $run['id'] );
		Mindio_Test::same( 'stopping', $state['status'], 'run is stopping' );

		// Cancelling a stream aborts the transfer instead of reading it to the end.
		$words = implode( ' ', array_map( static fn( int $n ): string => 'word' . $n, range( 1, 60 ) ) );
		Mindio_Test::script( array( array( 'text' => $words, 'delay_ms' => 100 ) ) );
		$started  = microtime( true );
		$received = '';
		$response = \MindioMagicAI\AI\Gateway::chat(
			( new \MindioMagicAI\AI\Connections() )->get( (int) $connection['id'] ),
			new \MindioMagicAI\AI\ChatRequest( model: 'gpt-test', system: 'test', messages: array( array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => 'long' ) ) ) ) ),
			'assistant',
			0,
			static function ( array $event ) use ( &$received ): bool {
				$received .= (string) ( $event['text'] ?? '' );
				return ! str_contains( $received, 'word3' );
			}
		);
		Mindio_Test::same( \MindioMagicAI\AI\ChatResponse::STOP_CANCELLED, $response->stop_reason, 'a cancelled stream reports it' );
		Mindio_Test::assert( microtime( true ) - $started < 2.0, 'a cancelled stream returns promptly' );
		Mindio_Test::assert( ! str_contains( $received, 'word20' ), 'no text arrives after the cancel' );
	}
);
