<?php
/**
 * Provider-neutral request for one model turn.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ChatRequest {
	/**
	 * @param array<int,array<string,mixed>> $messages Stored messages: role, content (normalized blocks), raw, provider.
	 * @param array<int,array{name:string,description:string,schema:array<string,mixed>}> $tools
	 */
	public function __construct(
		public readonly string $model,
		public readonly string $system,
		public readonly array $messages,
		public readonly array $tools = array(),
		public readonly int $max_tokens = 4096,
		public readonly string $effort = 'medium',
		public readonly string $context = '',
		public readonly string $user_hash = '',
	) {}

	/**
	 * Rough worst-case input size used for budget reservations (about 3.5 characters per token).
	 */
	public function estimated_input_tokens(): int {
		$size = strlen( $this->system ) + strlen( $this->context ) + strlen( (string) wp_json_encode( $this->tools ) );
		foreach ( $this->messages as $message ) {
			$size += strlen( (string) wp_json_encode( $message['content'] ?? array() ) );
		}
		return (int) ceil( $size / 3.5 ) + 50;
	}
}
