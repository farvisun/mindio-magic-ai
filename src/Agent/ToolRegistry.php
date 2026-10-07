<?php
/**
 * The tools available to the admin assistant.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Agent;

use MindioMagicAI\Agent\Tools\CommentTools;
use MindioMagicAI\Agent\Tools\ContentTools;
use MindioMagicAI\Agent\Tools\SeoTools;
use MindioMagicAI\Agent\Tools\SiteTools;
use MindioMagicAI\Agent\Tools\WooCommerceTools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ToolRegistry {
	/** @var array<string,Tool> */
	private array $tools = array();

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->register_defaults();
		}
		return self::$instance;
	}

	public static function reset(): void {
		self::$instance = null;
	}

	public function register( Tool $tool ): void {
		$this->tools[ $tool->name ] = $tool;
	}

	public function get( string $name ): ?Tool {
		return $this->tools[ $name ] ?? null;
	}

	/**
	 * Tools for a model request, in a stable order so the prompt cache stays valid.
	 *
	 * @return array<int,Tool>
	 */
	public function all(): array {
		$tools = $this->tools;
		ksort( $tools );
		return array_values( $tools );
	}

	private function register_defaults(): void {
		SiteTools::register( $this );
		ContentTools::register( $this );
		CommentTools::register( $this );
		SeoTools::register( $this );
		if ( class_exists( 'WooCommerce' ) ) {
			WooCommerceTools::register( $this );
		}

		/**
		 * Register additional assistant tools.
		 *
		 * @param ToolRegistry $registry
		 */
		do_action( 'mindio_magic_ai_register_tools', $this );
	}
}
