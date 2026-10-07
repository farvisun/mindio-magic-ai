<?php
/**
 * Site overview tool.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Agent\Tools;

use MindioMagicAI\Agent\Tool;
use MindioMagicAI\Agent\ToolRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SiteTools {
	public static function register( ToolRegistry $registry ): void {
		$registry->register(
			new Tool(
				name: 'site_overview',
				label: __( 'Read site overview', 'mindio-magic-ai' ),
				description: 'Get the site name, tagline, language, timezone, active theme, content counts, and whether WooCommerce is active. Use it to orient yourself before planning site-wide work.',
				schema: array(
					'type'                 => 'object',
					'properties'           => new \stdClass(),
					'additionalProperties' => false,
				),
				risk: Tool::RISK_READ,
				permission: static fn(): bool => current_user_can( 'edit_posts' ),
				handler: array( self::class, 'overview' ),
				group: 'site',
			)
		);
	}

	/** @return array<string,mixed> */
	public static function overview(): array {
		$counts = array();
		foreach ( array( 'post', 'page', 'product' ) as $type ) {
			if ( ! post_type_exists( $type ) ) {
				continue;
			}
			$count           = wp_count_posts( $type );
			$counts[ $type ] = array(
				'published' => (int) ( $count->publish ?? 0 ),
				'drafts'    => (int) ( $count->draft ?? 0 ),
				'pending'   => (int) ( $count->pending ?? 0 ),
				'scheduled' => (int) ( $count->future ?? 0 ),
			);
		}
		$comments = wp_count_comments();
		$theme    = wp_get_theme();

		$out = array(
			'name'        => html_entity_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ),
			'tagline'     => html_entity_decode( (string) get_bloginfo( 'description' ), ENT_QUOTES, 'UTF-8' ),
			'url'         => home_url( '/' ),
			'language'    => get_locale(),
			'timezone'    => wp_timezone_string(),
			'theme'       => $theme->get( 'Name' ),
			'content'     => $counts,
			'comments'    => array(
				'pending'  => (int) $comments->moderated,
				'approved' => (int) $comments->approved,
			),
			'seo_plugin'  => SeoTools::provider(),
			'woocommerce' => class_exists( 'WooCommerce' ) ? array(
				'version'  => defined( 'WC_VERSION' ) ? WC_VERSION : '',
				'currency' => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
			) : false,
		);
		return $out;
	}
}
