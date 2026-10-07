<?php
/**
 * Read-only PSR-7 stream whose bytes are produced by a suspended Fiber.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI\Transport;

use MindioMagicAI\Vendor\Psr\Http\Message\StreamInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FiberStream implements StreamInterface {
	private string $buffer;
	private bool $done;
	private int $position = 0;

	public function __construct( private ?\Fiber $fiber, string $first, private \Closure $cancel ) {
		$this->buffer = $first;
		$this->done   = null === $fiber || $fiber->isTerminated();
	}

	public function __destruct() {
		$this->close();
	}

	private function pull(): void {
		if ( $this->done || null === $this->fiber ) {
			$this->done = true;
			return;
		}
		$chunk = $this->fiber->resume();
		if ( $this->fiber->isTerminated() ) {
			$this->done = true;
			return;
		}
		$this->buffer .= (string) $chunk;
	}

	public function read( int $length ): string {
		while ( '' === $this->buffer && ! $this->done ) {
			$this->pull();
		}
		$out          = (string) substr( $this->buffer, 0, max( 1, $length ) );
		$this->buffer = (string) substr( $this->buffer, strlen( $out ) );
		$this->position += strlen( $out );
		return $out;
	}

	public function eof(): bool {
		return '' === $this->buffer && $this->done;
	}

	/**
	 * Cancel the transfer and drain the Fiber so cURL releases the connection.
	 */
	public function close(): void {
		if ( ! $this->done && null !== $this->fiber && $this->fiber->isSuspended() ) {
			( $this->cancel )();
			while ( ! $this->fiber->isTerminated() && $this->fiber->isSuspended() ) {
				$this->fiber->resume();
			}
		}
		$this->done   = true;
		$this->buffer = '';
		$this->fiber  = null;
	}

	public function getContents(): string {
		$out = '';
		while ( ! $this->eof() ) {
			$out .= $this->read( 65536 );
		}
		return $out;
	}

	public function __toString(): string {
		try {
			return $this->getContents();
		} catch ( \Throwable ) {
			return '';
		}
	}

	public function detach() {
		$this->close();
		return null;
	}

	public function getSize(): ?int {
		return null;
	}

	public function tell(): int {
		return $this->position;
	}

	public function isSeekable(): bool {
		return false;
	}

	public function seek( int $offset, int $whence = SEEK_SET ): void {
		throw new \RuntimeException( 'Stream is not seekable.' );
	}

	public function rewind(): void {
		throw new \RuntimeException( 'Stream is not seekable.' );
	}

	public function isWritable(): bool {
		return false;
	}

	public function write( string $string ): int {
		throw new \RuntimeException( 'Stream is not writable.' );
	}

	public function isReadable(): bool {
		return true;
	}

	public function getMetadata( ?string $key = null ) {
		return null === $key ? array() : null;
	}
}
