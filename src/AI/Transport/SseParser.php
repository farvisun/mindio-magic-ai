<?php
/**
 * Incremental server-sent events parser.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI\Transport;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SseParser {
	private string $buffer = '';
	private string $event  = '';

	/** @var array<int,string> */
	private array $data = array();

	/**
	 * Feed a chunk and return the complete events it finished.
	 *
	 * @return array<int,array{event:string,data:string}>
	 */
	public function feed( string $chunk ): array {
		$this->buffer .= $chunk;
		$events        = array();

		while ( preg_match( '/\r\n|\n|\r/', $this->buffer, $match, PREG_OFFSET_CAPTURE ) ) {
			$position     = (int) $match[0][1];
			$line         = substr( $this->buffer, 0, $position );
			$this->buffer = (string) substr( $this->buffer, $position + strlen( $match[0][0] ) );

			if ( '' === $line ) {
				if ( $this->data ) {
					$events[] = array(
						'event' => '' === $this->event ? 'message' : $this->event,
						'data'  => implode( "\n", $this->data ),
					);
				}
				$this->event = '';
				$this->data  = array();
				continue;
			}
			if ( str_starts_with( $line, ':' ) ) {
				continue;
			}

			$colon = strpos( $line, ':' );
			$field = false === $colon ? $line : substr( $line, 0, $colon );
			$value = false === $colon ? '' : ltrim( substr( $line, $colon + 1 ), ' ' );
			if ( 'data' === $field ) {
				$this->data[] = $value;
			} elseif ( 'event' === $field ) {
				$this->event = $value;
			}
		}

		return $events;
	}

	/**
	 * Flush an event left without a trailing blank line.
	 *
	 * @return array<int,array{event:string,data:string}>
	 */
	public function finish(): array {
		$events = $this->feed( "\n\n" );
		$this->buffer = '';
		return $events;
	}
}
