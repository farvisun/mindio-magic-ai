<?php
/**
 * SEO title, description, and focus keyword through Yoast SEO or Rank Math.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Agent\Tools;

use MindioMagicAI\Agent\Tool;
use MindioMagicAI\Agent\ToolRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SeoTools {
	private const KEYS = array(
		'yoast'    => array(
			'title'         => '_yoast_wpseo_title',
			'description'   => '_yoast_wpseo_metadesc',
			'focus_keyword' => '_yoast_wpseo_focuskw',
		),
		'rankmath' => array(
			'title'         => 'rank_math_title',
			'description'   => 'rank_math_description',
			'focus_keyword' => 'rank_math_focus_keyword',
		),
	);

	public static function provider(): string {
		if ( defined( 'WPSEO_VERSION' ) ) {
			return 'yoast';
		}
		if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) ) {
			return 'rankmath';
		}
		return '';
	}

	public static function register( ToolRegistry $registry ): void {
		if ( '' === self::provider() ) {
			return;
		}

		$registry->register(
			new Tool(
				name: 'get_seo_meta',
				label: __( 'Read SEO fields', 'mindio-magic-ai' ),
				description: 'Read the SEO title, meta description, and focus keyword of a post, page, or product from the active SEO plugin.',
				schema: array(
					'type'                 => 'object',
					'properties'           => array(
						'id' => array( 'type' => 'integer' ),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				risk: Tool::RISK_READ,
				permission: static fn( array $args ): bool|\WP_Error => Support::can_edit( (int) $args['id'] ),
				handler: array( self::class, 'get' ),
				group: 'seo',
			)
		);

		$registry->register(
			new Tool(
				name: 'update_seo_meta',
				label: __( 'Edit SEO fields', 'mindio-magic-ai' ),
				description: 'Set the SEO title, meta description (aim for 120–155 characters), or focus keyword. Send only the fields to change.',
				schema: array(
					'type'                 => 'object',
					'properties'           => array(
						'id'            => array( 'type' => 'integer' ),
						'title'         => array( 'type' => 'string' ),
						'description'   => array( 'type' => 'string' ),
						'focus_keyword' => array( 'type' => 'string' ),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				risk: Tool::RISK_WRITE,
				permission: static fn( array $args ): bool|\WP_Error => Support::can_edit( (int) $args['id'] ),
				handler: array( self::class, 'update' ),
				preview: array( self::class, 'preview' ),
				fingerprint: static fn( array $args ): string => md5( (string) wp_json_encode( self::values( (int) $args['id'] ) ) ),
				reversible: true,
				group: 'seo',
			)
		);
	}

	/** @return array<string,string> */
	private static function values( int $id ): array {
		$keys = self::KEYS[ self::provider() ] ?? array();
		$out  = array();
		foreach ( $keys as $field => $meta ) {
			$out[ $field ] = (string) get_post_meta( $id, $meta, true );
		}
		return $out;
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function get( array $args ): array|\WP_Error {
		$post = Support::post( (int) $args['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		return array(
			'plugin' => self::provider(),
			'post'   => Support::post_summary( $post ),
		) + self::values( $post->ID );
	}

	/** @return array<string,string> */
	private static function fields( array $args ): array {
		$out = array();
		foreach ( array( 'title', 'description', 'focus_keyword' ) as $field ) {
			if ( isset( $args[ $field ] ) ) {
				$out[ $field ] = sanitize_text_field( (string) $args[ $field ] );
			}
		}
		return $out;
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function preview( array $args ): array|\WP_Error {
		$post = Support::post( (int) $args['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		$fields = self::fields( $args );
		if ( ! $fields ) {
			return new \WP_Error( 'nothing_to_change', __( 'No SEO fields to change were provided.', 'mindio-magic-ai' ) );
		}
		return array(
			'summary' => sprintf(
				/* translators: %s: content title. */
				__( 'Update SEO fields for “%s”', 'mindio-magic-ai' ),
				html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' )
			),
			'target'  => Support::target( $post ),
			'changes' => Support::diff(
				self::values( $post->ID ),
				$fields,
				array(
					'title'         => __( 'SEO title', 'mindio-magic-ai' ),
					'description'   => __( 'Meta description', 'mindio-magic-ai' ),
					'focus_keyword' => __( 'Focus keyword', 'mindio-magic-ai' ),
				)
			),
		);
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function update( array $args ): array|\WP_Error {
		$post = Support::post( (int) $args['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		$keys = self::KEYS[ self::provider() ] ?? array();
		foreach ( self::fields( $args ) as $field => $value ) {
			if ( isset( $keys[ $field ] ) ) {
				update_post_meta( $post->ID, $keys[ $field ], wp_slash( $value ) );
			}
		}
		return array(
			'seo'    => self::values( $post->ID ),
			'target' => Support::target( $post ),
		);
	}
}
