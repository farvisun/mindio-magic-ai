<?php
/**
 * Writes run events to the event log and, when streaming, to the open response.
 *
 * Text deltas are coalesced (about every 120 ms or 160 characters) so a
 * long answer does not become thousands of rows.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Agent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RunEmitter {
	private string $text   = '';
	private float $flushed = 0.0;

	/** @var callable(int,string,array<string,mixed>):void|null */
	private $writer;

	/**
	 * @param callable(int,string,array<string,mixed>):void|null $writer Receives each stored event (for SSE).
	 */
	public function __construct( private int $run_id, ?callable $writer = null ) {
		$this->writer  = $writer;
		$this->flushed = microtime( true );
	}

	public function text( string $delta ): void {
		$this->text .= $delta;
		if ( mb_strlen( $this->text ) >= 160 || microtime( true ) - $this->flushed >= 0.12 ) {
			$this->flush();
		}
	}

	/**
	 * @param array<string,mixed> $payload
	 */
	public function emit( string $type, array $payload = array() ): void {
		$this->flush();
		$this->store( $type, $payload );
	}

	public function flush(): void {
		$this->flushed = microtime( true );
		if ( '' === $this->text ) {
			return;
		}
		$text       = $this->text;
		$this->text = '';
		$this->store( 'token', array( 'text' => $text ) );
	}

	/** @param array<string,mixed> $payload */
	private function store( string $type, array $payload ): void {
		$id = RunEvents::append( $this->run_id, $type, $payload );
		if ( null !== $this->writer ) {
			call_user_func( $this->writer, $id, $type, $payload );
		}
	}
}
