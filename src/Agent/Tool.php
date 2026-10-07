<?php
/**
 * A capability the model can call.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Agent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Tool {
	public const RISK_READ        = 'read';
	public const RISK_WRITE       = 'write';
	public const RISK_DESTRUCTIVE = 'destructive';
	public const RISK_EXTERNAL    = 'external';

	/**
	 * @param string                                   $name        Model-facing name.
	 * @param string                                   $label       Translated label for the UI.
	 * @param string                                   $description Model-facing description.
	 * @param array<string,mixed>                      $schema      JSON Schema for the input.
	 * @param string                                   $risk        One of the RISK_* constants.
	 * @param callable(array<string,mixed>):(bool|\WP_Error)                 $permission Checked for the current user before every call and again before applying.
	 * @param callable(array<string,mixed>):(array<string,mixed>|\WP_Error) $handler    Performs the read or the write.
	 * @param callable(array<string,mixed>):(array<string,mixed>|\WP_Error)|null $preview   For writes: what will change, shown on the proposal card.
	 * @param callable(array<string,mixed>):string|null $fingerprint For writes: a value that changes when the target changes.
	 * @param bool                                     $reversible  Whether the change can be undone from its journal.
	 * @param bool                                     $auto_apply  Whether a user may let this write apply without review (drafts only).
	 * @param bool                                     $untrusted   Whether the result contains text written by visitors or third parties.
	 * @param string                                   $group       UI grouping.
	 */
	public function __construct(
		public readonly string $name,
		public readonly string $label,
		public readonly string $description,
		public readonly array $schema,
		public readonly string $risk,
		private $permission,
		private $handler,
		private $preview = null,
		private $fingerprint = null,
		public readonly bool $reversible = false,
		public readonly bool $auto_apply = false,
		public readonly bool $untrusted = false,
		public readonly string $group = 'content',
	) {}

	public function is_write(): bool {
		return self::RISK_READ !== $this->risk;
	}

	/** @param array<string,mixed> $args */
	public function permitted( array $args ): bool|\WP_Error {
		return call_user_func( $this->permission, $args );
	}

	/**
	 * @param array<string,mixed> $args
	 * @return array<string,mixed>|\WP_Error
	 */
	public function run( array $args ): array|\WP_Error {
		return call_user_func( $this->handler, $args );
	}

	/**
	 * @param array<string,mixed> $args
	 * @return array<string,mixed>|\WP_Error
	 */
	public function preview( array $args ): array|\WP_Error {
		return null === $this->preview ? array() : call_user_func( $this->preview, $args );
	}

	/** @param array<string,mixed> $args */
	public function fingerprint( array $args ): string {
		return null === $this->fingerprint ? '' : (string) call_user_func( $this->fingerprint, $args );
	}

	/** @return array{name:string,description:string,schema:array<string,mixed>} */
	public function definition(): array {
		return array(
			'name'        => $this->name,
			'description' => $this->description,
			'schema'      => $this->schema,
		);
	}
}
