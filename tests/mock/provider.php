<?php
/**
 * Scripted mock of the Anthropic Messages API and an OpenAI-compatible API, for tests.
 *
 * Run with: php -S 127.0.0.1:8765 tests/mock/provider.php
 * Tests write the turns to return into the queue file; each request pops one turn.
 * Every request is appended to the log file so tests can assert what was sent.
 *
 * @package MindioMagicAI
 */

declare(strict_types=1);

$dir   = getenv( 'MINDIO_MOCK_DIR' ) ?: sys_get_temp_dir() . '/mindio-ai-mock';
$queue = $dir . '/queue.json';
$log   = $dir . '/requests.jsonl';
if ( ! is_dir( $dir ) ) {
	mkdir( $dir, 0777, true );
}

$path    = (string) parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH );
$raw     = (string) file_get_contents( 'php://input' );
$body    = json_decode( $raw, true ) ?: array();
$headers = array_change_key_case( getallheaders() ?: array(), CASE_LOWER );

file_put_contents( $log, json_encode( array( 'path' => $path, 'method' => $_SERVER['REQUEST_METHOD'] ?? '', 'headers' => $headers, 'raw' => $raw ) ) . "\n", FILE_APPEND | LOCK_EX );

function mock_json( int $status, array $data ): void {
	http_response_code( $status );
	header( 'Content-Type: application/json' );
	echo json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	exit;
}

function mock_next_turn( string $queue ): array {
	$fp = fopen( $queue, 'c+' );
	flock( $fp, LOCK_EX );
	$items = json_decode( (string) stream_get_contents( $fp ), true ) ?: array();
	$turn  = array_shift( $items ) ?? array( 'text' => '(mock: no scripted turn)' );
	ftruncate( $fp, 0 );
	rewind( $fp );
	fwrite( $fp, json_encode( $items ) );
	flock( $fp, LOCK_UN );
	fclose( $fp );
	return $turn;
}

$anthropic = str_starts_with( $path, '/v1/' );
$openai    = str_starts_with( $path, '/openai/v1/' );
$key       = $anthropic ? (string) ( $headers['x-api-key'] ?? '' ) : preg_replace( '/^Bearer\s+/i', '', (string) ( $headers['authorization'] ?? '' ) );

if ( ! str_starts_with( (string) $key, 'sk-test-good' ) ) {
	mock_json( 401, $anthropic ? array( 'type' => 'error', 'error' => array( 'type' => 'authentication_error', 'message' => 'invalid x-api-key' ) ) : array( 'error' => array( 'message' => 'Incorrect API key provided', 'type' => 'invalid_request_error' ) ) );
}

if ( '/v1/models' === $path ) {
	mock_json( 200, array( 'data' => array( array( 'id' => 'claude-opus-5-5', 'type' => 'model', 'display_name' => 'Claude Opus 5.5', 'created_at' => '2026-08-01T00:00:00Z' ), array( 'id' => 'claude-haiku-4-5', 'type' => 'model', 'display_name' => 'Claude Haiku 4.5', 'created_at' => '2025-10-01T00:00:00Z' ) ), 'has_more' => false, 'first_id' => 'claude-opus-5-5', 'last_id' => 'claude-haiku-4-5' ) );
}
if ( '/openai/v1/models' === $path ) {
	mock_json( 200, array( 'object' => 'list', 'data' => array( array( 'id' => 'gpt-test', 'object' => 'model' ), array( 'id' => 'gpt-test-mini', 'object' => 'model' ) ) ) );
}

if ( '/v1/messages' !== $path && '/openai/v1/chat/completions' !== $path ) {
	mock_json( 404, array( 'error' => array( 'message' => 'not found' ) ) );
}

$turn = mock_next_turn( $queue );
if ( isset( $turn['status'] ) ) {
	mock_json( (int) $turn['status'], $turn['body'] ?? array( 'error' => array( 'type' => 'api_error', 'message' => 'scripted error' ) ) );
}

$text  = (string) ( $turn['text'] ?? '' );
$calls = (array) ( $turn['tool_calls'] ?? array() );
$delay = (int) ( $turn['delay_ms'] ?? 0 );
$model = (string) ( $body['model'] ?? 'mock' );
$stop  = $calls ? 'tool_use' : (string) ( $turn['stop_reason'] ?? 'end_turn' );
$parts = '' === $text ? array() : preg_split( '/(?<=\s)/u', $text );

if ( $anthropic ) {
	$content = array();
	if ( '' !== $text ) {
		$content[] = array( 'type' => 'text', 'text' => $text );
	}
	foreach ( $calls as $i => $call ) {
		$content[] = array( 'type' => 'tool_use', 'id' => 'toolu_' . substr( md5( $raw . $i ), 0, 16 ), 'name' => $call['name'], 'input' => (object) ( $call['input'] ?? array() ) );
	}
	$usage = array( 'input_tokens' => 120, 'output_tokens' => 40, 'cache_read_input_tokens' => 0, 'cache_creation_input_tokens' => 0 );

	if ( empty( $body['stream'] ) ) {
		mock_json( 200, array( 'id' => 'msg_mock', 'type' => 'message', 'role' => 'assistant', 'model' => $model, 'content' => $content, 'stop_reason' => $stop, 'stop_sequence' => null, 'usage' => $usage ) );
	}

	header( 'Content-Type: text/event-stream' );
	header( 'Cache-Control: no-cache' );
	while ( ob_get_level() ) {
		ob_end_flush();
	}
	$send = static function ( string $type, array $data ) use ( $delay ): void {
		echo 'event: ' . $type . "\ndata: " . json_encode( $data, JSON_UNESCAPED_UNICODE ) . "\n\n";
		flush();
		if ( $delay ) {
			usleep( $delay * 1000 );
		}
	};
	$send( 'message_start', array( 'type' => 'message_start', 'message' => array( 'id' => 'msg_mock', 'type' => 'message', 'role' => 'assistant', 'model' => $model, 'content' => array(), 'stop_reason' => null, 'stop_sequence' => null, 'usage' => array( 'input_tokens' => 120, 'output_tokens' => 1, 'cache_read_input_tokens' => 0, 'cache_creation_input_tokens' => 0 ) ) ) );
	$index = 0;
	if ( '' !== $text ) {
		$send( 'content_block_start', array( 'type' => 'content_block_start', 'index' => $index, 'content_block' => array( 'type' => 'text', 'text' => '' ) ) );
		foreach ( $parts as $part ) {
			$send( 'content_block_delta', array( 'type' => 'content_block_delta', 'index' => $index, 'delta' => array( 'type' => 'text_delta', 'text' => $part ) ) );
		}
		$send( 'content_block_stop', array( 'type' => 'content_block_stop', 'index' => $index ) );
		++$index;
	}
	foreach ( $content as $block ) {
		if ( 'tool_use' !== $block['type'] ) {
			continue;
		}
		$send( 'content_block_start', array( 'type' => 'content_block_start', 'index' => $index, 'content_block' => array( 'type' => 'tool_use', 'id' => $block['id'], 'name' => $block['name'], 'input' => new stdClass() ) ) );
		$json = json_encode( $block['input'], JSON_UNESCAPED_UNICODE );
		foreach ( mb_str_split( $json, 7 ) as $piece ) {
			$send( 'content_block_delta', array( 'type' => 'content_block_delta', 'index' => $index, 'delta' => array( 'type' => 'input_json_delta', 'partial_json' => $piece ) ) );
		}
		$send( 'content_block_stop', array( 'type' => 'content_block_stop', 'index' => $index ) );
		++$index;
	}
	$send( 'message_delta', array( 'type' => 'message_delta', 'delta' => array( 'stop_reason' => $stop, 'stop_sequence' => null ), 'usage' => array( 'output_tokens' => 40 ) ) );
	$send( 'message_stop', array( 'type' => 'message_stop' ) );
	exit;
}

// OpenAI-compatible.
$tool_calls = array();
foreach ( $calls as $i => $call ) {
	$tool_calls[] = array( 'id' => 'call_' . substr( md5( $raw . $i ), 0, 12 ), 'type' => 'function', 'function' => array( 'name' => $call['name'], 'arguments' => json_encode( (object) ( $call['input'] ?? array() ), JSON_UNESCAPED_UNICODE ) ) );
}
$finish = $calls ? 'tool_calls' : 'stop';
$usage  = array( 'prompt_tokens' => 100, 'completion_tokens' => 30, 'total_tokens' => 130 );

if ( empty( $body['stream'] ) ) {
	$message = array( 'role' => 'assistant', 'content' => '' === $text ? null : $text );
	if ( $tool_calls ) {
		$message['tool_calls'] = $tool_calls;
	}
	mock_json( 200, array( 'id' => 'chatcmpl-mock', 'object' => 'chat.completion', 'model' => $model, 'choices' => array( array( 'index' => 0, 'message' => $message, 'finish_reason' => $finish ) ), 'usage' => $usage ) );
}

header( 'Content-Type: text/event-stream' );
while ( ob_get_level() ) {
	ob_end_flush();
}
$chunk = static function ( array $delta, ?string $finish_reason = null ) use ( $model, $delay ): void {
	echo 'data: ' . json_encode( array( 'id' => 'chatcmpl-mock', 'object' => 'chat.completion.chunk', 'model' => $model, 'choices' => array( array( 'index' => 0, 'delta' => $delta, 'finish_reason' => $finish_reason ) ) ), JSON_UNESCAPED_UNICODE ) . "\n\n";
	flush();
	if ( $delay ) {
		usleep( $delay * 1000 );
	}
};
$chunk( array( 'role' => 'assistant', 'content' => '' ) );
foreach ( $parts as $part ) {
	$chunk( array( 'content' => $part ) );
}
foreach ( $tool_calls as $i => $call ) {
	$chunk( array( 'tool_calls' => array( array( 'index' => $i, 'id' => $call['id'], 'type' => 'function', 'function' => array( 'name' => $call['function']['name'], 'arguments' => '' ) ) ) ) );
	foreach ( mb_str_split( $call['function']['arguments'], 9 ) as $piece ) {
		$chunk( array( 'tool_calls' => array( array( 'index' => $i, 'function' => array( 'arguments' => $piece ) ) ) ) );
	}
}
$chunk( array(), $finish );
if ( ! empty( $body['stream_options']['include_usage'] ) && empty( $turn['omit_usage'] ) ) {
	echo 'data: ' . json_encode( array( 'id' => 'chatcmpl-mock', 'object' => 'chat.completion.chunk', 'model' => $model, 'choices' => array(), 'usage' => $usage ) ) . "\n\n";
}
echo "data: [DONE]\n\n";
flush();
