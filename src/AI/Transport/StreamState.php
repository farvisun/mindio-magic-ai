<?php
/**
 * Mutable state shared between a streaming request and its cURL callbacks.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI\Transport;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class StreamState {
	public int $status = 0;

	/** @var array<string,array<int,string>> */
	public array $headers = array();

	public bool $cancelled = false;

	public bool $hooked = false;

	/** @var callable(string,StreamState):bool|null */
	public $on_chunk = null;
}
