<?php
/**
 * End-to-end assistant flow against the scripted Anthropic mock: connection, run, tools, proposal, apply, undo.
 *
 * @package MindioMagicAI
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

Mindio_Test::run(
	'assistant-flow',
	static function (): void {
		$admin = Mindio_Test::user( 'administrator' );
		Mindio_Test::as( $admin );
		$secret = 'sk-test-good-ABCD1234';
		$seen   = array();

		// Connection: the key is write-only.
		$created = Mindio_Test::rest(
			'POST',
			'/connections',
			array(
				'preset'   => 'anthropic_custom',
				'name'     => 'Mock Claude',
				'base_url' => 'http://' . MINDIO_TEST_MOCK,
				'api_key'  => $secret,
				'headers'  => array( array( 'name' => 'X-Team', 'value' => 'secret-header-value' ) ),
			)
		);
		$seen[] = $created;
		Mindio_Test::same( 200, $created['status'], 'connection is created' );
		$connection = $created['data'];
		Mindio_Test::same( '1234', $connection['key_hint'], 'only the last four characters are exposed' );
		Mindio_Test::same( array( 'X-Team' ), $connection['header_names'], 'header names are listed without values' );
		Mindio_Test::same( 'active', $connection['status'], 'connection with key and model is active' );

		Mindio_Test::script( array() ); // Start with an empty request log.
		$test   = Mindio_Test::rest( 'POST', '/connections/' . $connection['id'] . '/test' );
		$seen[] = $test;
		Mindio_Test::same( 200, $test['status'], 'connection test succeeds' );
		Mindio_Test::same( 2, $test['data']['models'], 'models are listed' );
		$requests = Mindio_Test::requests();
		Mindio_Test::same( 'secret-header-value', $requests[0]['headers']['x-team'] ?? '', 'extra header reaches the provider' );

		$bad = Mindio_Test::rest( 'POST', '/connections', array( 'preset' => 'anthropic_custom', 'base_url' => 'http://' . MINDIO_TEST_MOCK, 'api_key' => 'sk-test-wrong' ) );
		$bad_test = Mindio_Test::rest( 'POST', '/connections/' . $bad['data']['id'] . '/test' );
		Mindio_Test::same( 'provider_auth', $bad_test['data']['code'] ?? '', 'a rejected key is reported clearly' );
		Mindio_Test::rest( 'DELETE', '/connections/' . $bad['data']['id'] );

		// Conversation and a run with a read tool, then a write proposal.
		$post_id = Mindio_Test::post( array( 'post_title' => 'Mindio flow original', 'post_content' => '<!-- wp:paragraph --><p>Body</p><!-- /wp:paragraph -->' ) );
		$conv    = Mindio_Test::rest( 'POST', '/conversations', array( 'connection_id' => $connection['id'] ) );
		Mindio_Test::same( 200, $conv['status'], 'conversation is created' );
		$conversation_id = (int) $conv['data']['id'];
		Mindio_Test::same( 'claude-opus-5-5', $conv['data']['model'], 'conversation pins the default model' );

		Mindio_Test::script(
			array(
				array( 'tool_calls' => array( array( 'name' => 'search_content', 'input' => array( 'query' => 'Mindio flow' ) ) ) ),
				array(
					'text'       => 'Found it. ',
					'tool_calls' => array( array( 'name' => 'update_content', 'input' => array( 'id' => $post_id, 'title' => 'Mindio flow improved' ) ) ),
				),
				array( 'text' => 'I proposed a clearer title. Apply it when you are ready.' ),
			)
		);

		$run = Mindio_Test::rest( 'POST', '/conversations/' . $conversation_id . '/runs', array( 'message' => 'Improve the title of the Mindio flow post', 'screen' => array( 'screen' => 'post', 'post_id' => $post_id ) ) );
		Mindio_Test::same( 200, $run['status'], 'run is queued' );
		Mindio_Test::same( 'queued', $run['data']['status'], 'run starts queued' );
		$run_id = (int) $run['data']['id'];

		$busy = Mindio_Test::rest( 'POST', '/conversations/' . $conversation_id . '/runs', array( 'message' => 'second' ) );
		Mindio_Test::same( 409, $busy['status'], 'only one active run per conversation' );

		$exec = Mindio_Test::rest( 'POST', '/runs/' . $run_id . '/execute' );
		Mindio_Test::same( 200, $exec['status'], 'run executes' );
		Mindio_Test::same( 'completed', $exec['data']['status'], 'run completes' );
		Mindio_Test::same( 3, $exec['data']['steps'], 'three model turns' );

		$again = Mindio_Test::rest( 'POST', '/runs/' . $run_id . '/execute' );
		Mindio_Test::same( 409, $again['status'], 'a run cannot be executed twice' );

		$events = Mindio_Test::rest( 'GET', '/runs/' . $run_id . '/events', null, array( 'after' => 0 ) );
		$types  = array_column( $events['data']['events'], 'type' );
		foreach ( array( 'start', 'tool_start', 'tool_run', 'tool_result', 'proposal', 'token', 'usage', 'done' ) as $type ) {
			Mindio_Test::assert( in_array( $type, $types, true ), 'event stream contains ' . $type );
		}
		$tokens = implode( '', array_map( static fn( array $e ): string => 'token' === $e['type'] ? (string) $e['payload']['text'] : '', $events['data']['events'] ) );
		Mindio_Test::assert( str_contains( $tokens, 'I proposed a clearer title.' ), 'streamed text is recorded' );
		$last_id = (int) end( $events['data']['events'] )['id'];
		$after   = Mindio_Test::rest( 'GET', '/runs/' . $run_id . '/events', null, array( 'after' => $last_id ) );
		Mindio_Test::same( array(), $after['data']['events'], 'polling after the last event returns nothing new' );

		// What the provider received.
		$calls = array_values( array_filter( Mindio_Test::requests(), static fn( array $r ): bool => '/v1/messages' === $r['path'] ) );
		Mindio_Test::same( 3, count( $calls ), 'three requests reached the provider' );
		$first = json_decode( $calls[0]['raw'], true );
		Mindio_Test::assert( ! empty( $first['stream'] ), 'requests stream' );
		Mindio_Test::same( array( 'type' => 'ephemeral' ), $first['system'][0]['cache_control'] ?? null, 'system prompt is cached' );
		Mindio_Test::assert( str_contains( (string) ( $first['system'][1]['text'] ?? '' ), 'Mindio flow original' ), 'screen context follows the cache breakpoint' );
		Mindio_Test::same( array( 'type' => 'auto' ), $first['tool_choice'] ?? null, 'tool choice is auto' );
		Mindio_Test::assert( ! empty( $first['tools'][0]['eager_input_streaming'] ), 'tools stream input eagerly' );
		Mindio_Test::assert( isset( end( $first['tools'] )['cache_control'] ), 'tool definitions are cached' );
		Mindio_Test::assert( ! isset( $first['thinking'] ), 'Opus 5.5 gets no thinking parameter' );
		Mindio_Test::same( 'medium', $first['output_config']['effort'] ?? null, 'effort is sent' );
		Mindio_Test::assert( 1 === preg_match( '/^[a-f0-9]{32,}$/', (string) ( $first['metadata']['user_id'] ?? '' ) ) && (string) $admin !== (string) $first['metadata']['user_id'], 'user id is sent as an opaque hash' );
		$second = json_decode( $calls[1]['raw'], true );
		$last   = end( $second['messages'] );
		Mindio_Test::same( 'tool_result', $last['content'][0]['type'] ?? '', 'tool result is sent back' );
		Mindio_Test::assert( str_contains( (string) $last['content'][0]['content'], 'Mindio flow original' ), 'search result contains the post' );
		$third = json_decode( $calls[2]['raw'], true );
		$prev  = $third['messages'][ count( $third['messages'] ) - 2 ];
		Mindio_Test::same( 'tool_use', $prev['content'][1]['type'] ?? '', 'assistant tool use is replayed verbatim' );
		Mindio_Test::same( 'Mindio flow improved', $prev['content'][1]['input']['title'] ?? '', 'streamed tool input was parsed' );

		// Proposal lifecycle.
		Mindio_Test::same( 'Mindio flow original', get_post( $post_id )->post_title, 'nothing changes before approval' );
		$detail    = Mindio_Test::rest( 'GET', '/conversations/' . $conversation_id );
		$proposals = $detail['data']['proposals'];
		Mindio_Test::same( 1, count( $proposals ), 'one proposal' );
		$proposal = $proposals[0];
		Mindio_Test::same( 'pending', $proposal['status'], 'proposal is pending' );
		Mindio_Test::same( 'post_title', $proposal['preview']['changes'][0]['field'], 'preview shows the title diff' );
		Mindio_Test::assert( ! isset( $proposal['arguments'] ), 'arguments are not sent to the browser' );

		$other = Mindio_Test::user( 'administrator' );
		Mindio_Test::as( $other );
		Mindio_Test::same( 404, Mindio_Test::rest( 'POST', '/proposals/' . $proposal['id'] . '/apply' )['status'], 'another user cannot apply the proposal' );
		Mindio_Test::same( 404, Mindio_Test::rest( 'GET', '/conversations/' . $conversation_id )['status'], 'another user cannot read the conversation' );
		Mindio_Test::as( $admin );

		$applied = Mindio_Test::rest( 'POST', '/proposals/' . $proposal['id'] . '/apply' );
		Mindio_Test::same( 200, $applied['status'], 'proposal applies' );
		Mindio_Test::same( 'applied', $applied['data']['status'], 'proposal is applied' );
		Mindio_Test::assert( $applied['data']['can_undo'], 'applied change can be undone' );
		clean_post_cache( $post_id );
		Mindio_Test::same( 'Mindio flow improved', get_post( $post_id )->post_title, 'title changed' );
		Mindio_Test::same( 409, Mindio_Test::rest( 'POST', '/proposals/' . $proposal['id'] . '/apply' )['status'], 'a proposal applies only once' );

		$undo = Mindio_Test::rest( 'POST', '/proposals/' . $proposal['id'] . '/undo' );
		Mindio_Test::same( 200, $undo['status'], 'undo succeeds' );
		clean_post_cache( $post_id );
		Mindio_Test::same( 'Mindio flow original', get_post( $post_id )->post_title, 'title restored' );
		Mindio_Test::same( 409, Mindio_Test::rest( 'POST', '/proposals/' . $proposal['id'] . '/undo' )['status'], 'undo runs once' );

		// The next user turn tells the model what happened to the proposal.
		Mindio_Test::script( array( array( 'tool_calls' => array( array( 'name' => 'update_content', 'input' => array( 'id' => $post_id, 'excerpt' => 'A new excerpt' ) ) ) ), array( 'text' => 'Proposed.' ) ) );
		$run2 = Mindio_Test::rest( 'POST', '/conversations/' . $conversation_id . '/runs', array( 'message' => 'Add an excerpt' ) );
		Mindio_Test::rest( 'POST', '/runs/' . $run2['data']['id'] . '/execute' );
		$req   = json_decode( Mindio_Test::requests()[0]['raw'], true );
		$user  = end( $req['messages'] );
		$found = false;
		foreach ( $user['content'] as $block ) {
			if ( 'text' === $block['type'] && str_contains( $block['text'], $proposal['id'] ) && str_contains( $block['text'], 'reverted' ) ) {
				$found = true;
			}
		}
		Mindio_Test::assert( $found, 'proposal status note reaches the model' );

		// Stale proposals are refused.
		$pending = array_values( array_filter( Mindio_Test::rest( 'GET', '/conversations/' . $conversation_id )['data']['proposals'], static fn( array $p ): bool => 'pending' === $p['status'] ) );
		Mindio_Test::same( 1, count( $pending ), 'second proposal is pending' );
		wp_update_post( array( 'ID' => $post_id, 'post_content' => 'Edited by a human meanwhile' ) );
		$stale = Mindio_Test::rest( 'POST', '/proposals/' . $pending[0]['id'] . '/apply' );
		Mindio_Test::same( 409, $stale['status'], 'stale proposal is refused' );
		Mindio_Test::same( 'stale', $stale['data']['code'], 'stale error code' );

		// Undo refuses to overwrite later edits unless forced.
		Mindio_Test::script( array( array( 'tool_calls' => array( array( 'name' => 'update_content', 'input' => array( 'id' => $post_id, 'title' => 'AI title' ) ) ) ), array( 'text' => 'Done.' ) ) );
		$run3 = Mindio_Test::rest( 'POST', '/conversations/' . $conversation_id . '/runs', array( 'message' => 'Retitle' ) );
		Mindio_Test::rest( 'POST', '/runs/' . $run3['data']['id'] . '/execute' );
		$pending = array_values( array_filter( Mindio_Test::rest( 'GET', '/conversations/' . $conversation_id )['data']['proposals'], static fn( array $p ): bool => 'pending' === $p['status'] ) );
		Mindio_Test::same( 200, Mindio_Test::rest( 'POST', '/proposals/' . $pending[0]['id'] . '/apply' )['status'], 'third proposal applies' );
		wp_update_post( array( 'ID' => $post_id, 'post_title' => 'Human retitled it' ) );
		$conflict = Mindio_Test::rest( 'POST', '/proposals/' . $pending[0]['id'] . '/undo' );
		Mindio_Test::same( 'undo_conflict', $conflict['data']['code'] ?? '', 'undo detects the later edit' );
		clean_post_cache( $post_id );
		Mindio_Test::same( 'Human retitled it', get_post( $post_id )->post_title, 'conflicting undo changes nothing' );
		Mindio_Test::same( 200, Mindio_Test::rest( 'POST', '/proposals/' . $pending[0]['id'] . '/undo', array( 'force' => true ) )['status'], 'forced undo succeeds' );

		// Reject.
		Mindio_Test::script( array( array( 'tool_calls' => array( array( 'name' => 'create_draft', 'input' => array( 'post_type' => 'post', 'title' => 'Draft from AI', 'content' => '<p>Hello<script>alert(1)</script></p>' ) ) ) ), array( 'text' => 'Drafted.' ) ) );
		$run4 = Mindio_Test::rest( 'POST', '/conversations/' . $conversation_id . '/runs', array( 'message' => 'Draft a post' ) );
		Mindio_Test::rest( 'POST', '/runs/' . $run4['data']['id'] . '/execute' );
		$pending = array_values( array_filter( Mindio_Test::rest( 'GET', '/conversations/' . $conversation_id )['data']['proposals'], static fn( array $p ): bool => 'pending' === $p['status'] && 'create_draft' === $p['tool'] ) );
		Mindio_Test::assert( ! str_contains( (string) wp_json_encode( $pending[0]['preview'] ), '<script>' ), 'preview content is filtered' );
		$applied = Mindio_Test::rest( 'POST', '/proposals/' . $pending[0]['id'] . '/apply' );
		$created = (int) $applied['data']['result']['target']['id'];
		Mindio_Test::track_post( $created );
		Mindio_Test::same( 'draft', get_post_status( $created ), 'AI drafts are drafts' );
		Mindio_Test::assert( ! str_contains( get_post( $created )->post_content, '<script' ), 'generated HTML is filtered on save' );
		$undo_draft = Mindio_Test::rest( 'POST', '/proposals/' . $pending[0]['id'] . '/undo' );
		Mindio_Test::same( 200, $undo_draft['status'], 'undoing a created draft succeeds: ' . wp_json_encode( $undo_draft['data'] ) );
		clean_post_cache( $created );
		Mindio_Test::same( 'trash', get_post_status( $created ), 'undoing a created draft trashes it' );

		// Cancelling a stream stops the transfer at once instead of reading it to the end.
		$words = implode( ' ', array_map( static fn( int $n ): string => 'word' . $n, range( 1, 60 ) ) );
		Mindio_Test::script( array( array( 'text' => $words, 'delay_ms' => 100 ) ) );
		$started  = microtime( true );
		$received = '';
		$response = \MindioMagicAI\AI\Gateway::chat(
			( new \MindioMagicAI\AI\Connections() )->get( (int) $connection['id'] ),
			new \MindioMagicAI\AI\ChatRequest( model: 'claude-opus-5-5', system: 'test', messages: array( array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => 'long' ) ) ) ) ),
			'assistant',
			0,
			static function ( array $event ) use ( &$received ): bool {
				$received .= (string) ( $event['text'] ?? '' );
				return ! str_contains( $received, 'word3' );
			}
		);
		$elapsed = microtime( true ) - $started;
		Mindio_Test::same( \MindioMagicAI\AI\ChatResponse::STOP_CANCELLED, $response->stop_reason, 'a cancelled stream reports it' );
		Mindio_Test::assert( $elapsed < 2.0, sprintf( 'a cancelled stream returns promptly (%.1fs for a 6s stream)', $elapsed ) );
		Mindio_Test::assert( ! str_contains( $received, 'word20' ), 'no text arrives after the cancel' );
		Mindio_Test::same( 0, \MindioMagicAI\AI\Transport\Psr18Client::open_transfers(), 'a cancelled stream releases its transfer before returning' );

		// Usage was recorded.
		$usage = Mindio_Test::rest( 'GET', '/usage', null, array( 'days' => 7 ) );
		Mindio_Test::assert( $usage['data']['month']['calls'] >= 9, 'usage ledger records each call' );
		Mindio_Test::assert( $usage['data']['month']['cost_micros'] > 0, 'priced model has a cost' );

		// The key never leaves the server.
		$seen[] = Mindio_Test::rest( 'GET', '/bootstrap' );
		$seen[] = Mindio_Test::rest( 'GET', '/connections' );
		$seen[] = Mindio_Test::rest( 'GET', '/audit' );
		$seen[] = Mindio_Test::rest( 'GET', '/conversations/' . $conversation_id );
		foreach ( $seen as $response ) {
			$json = (string) wp_json_encode( $response );
			Mindio_Test::assert( ! str_contains( $json, $secret ) && ! str_contains( $json, 'secret-header-value' ), 'no response contains a secret' );
		}
		global $wpdb;
		$stored = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT enc_key FROM %i WHERE id = %d', \MindioMagicAI\Core\Installer::table( 'connections' ), $connection['id'] ) );
		Mindio_Test::assert( '' !== $stored && ! str_contains( $stored, 'ABCD1234' ), 'key is encrypted at rest' );
		$audit_json = (string) wp_json_encode( $wpdb->get_results( $wpdb->prepare( 'SELECT details FROM %i', \MindioMagicAI\Core\Installer::table( 'audit_log' ) ) ) );
		Mindio_Test::assert( ! str_contains( $audit_json, $secret ), 'audit log has no secret' );

		// Changing the address discards the key.
		\MindioMagicAI\Core\Settings::update( array( 'private_hosts' => array( MINDIO_TEST_MOCK, 'localhost:8766' ) ) );
		$moved = Mindio_Test::rest( 'PATCH', '/connections/' . $connection['id'], array( 'base_url' => 'http://localhost:8766' ) );
		Mindio_Test::same( 'none', $moved['data']['key_source'], 'moving the connection clears the key' );
		Mindio_Test::same( array(), $moved['data']['header_names'], 'moving the connection clears the headers' );
		Mindio_Test::same( 'needs_key', $moved['data']['status'], 'connection needs a key again' );
	}
);
