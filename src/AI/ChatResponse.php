<?php
/**
 * Provider-neutral result of one model turn.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ChatResponse {
	public const STOP_END       = 'end_turn';
	public const STOP_TOOL_USE  = 'tool_use';
	public const STOP_MAX       = 'max_tokens';
	public const STOP_REFUSAL   = 'refusal';
	public const STOP_CANCELLED = 'cancelled';
	public const STOP_OTHER     = 'other';

	/**
	 * @param array<int,array<string,mixed>> $content Normalized blocks: text and tool_call ({id,name,input,valid}).
	 * @param mixed                          $raw     The assistant turn in the provider's wire format, replayed verbatim.
	 * @param array{input:int,output:int,cache_read:int,cache_write:int} $usage
	 */
	public function __construct(
		public readonly array $content,
		public readonly mixed $raw,
		public readonly string $stop_reason,
		public readonly array $usage,
		public readonly string $model,
		public readonly bool $usage_estimated = false,
		public readonly string $refusal_category = '',
	) {}

	public function text(): string {
		$text = '';
		foreach ( $this->content as $block ) {
			if ( 'text' === ( $block['type'] ?? '' ) ) {
				$text .= (string) $block['text'];
			}
		}
		return $text;
	}

	/** @return array<int,array<string,mixed>> */
	public function tool_calls(): array {
		return array_values( array_filter( $this->content, static fn( array $block ): bool => 'tool_call' === ( $block['type'] ?? '' ) ) );
	}

	public function total_tokens(): int {
		return $this->usage['input'] + $this->usage['output'] + $this->usage['cache_read'] + $this->usage['cache_write'];
	}
}
