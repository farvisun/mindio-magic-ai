<?php
/**
 * A model provider adapter.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Provider {
	/**
	 * Run one model turn. When $on_event is given the response is streamed and the
	 * callback receives events such as array{type:'text',text:string} and
	 * array{type:'tool_start',name:string}. Returning false from it cancels the turn.
	 *
	 * @param callable(array<string,mixed>):bool|null $on_event
	 */
	public function chat( ChatRequest $request, ?callable $on_event = null ): ChatResponse|\WP_Error;

	/** @return array<int,string>|\WP_Error */
	public function list_models(): array|\WP_Error;
}
