<?php
/**
 * Shared helpers for assistant tools.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Agent\Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Support {
	public const CONTENT_LIMIT = 30000;

	/** @return array<string,mixed> */
	public static function post_summary( \WP_Post $post ): array {
		return array(
			'id'        => $post->ID,
			'type'      => $post->post_type,
			'title'     => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
			'status'    => $post->post_status,
			'date'      => mysql_to_rfc3339( $post->post_date ),
			'modified'  => mysql_to_rfc3339( $post->post_modified ),
			'link'      => (string) get_permalink( $post ),
			'edit_link' => (string) get_edit_post_link( $post->ID, 'raw' ),
		);
	}

	public static function clip( string $text, int $limit = self::CONTENT_LIMIT ): string {
		return mb_strlen( $text ) > $limit ? mb_substr( $text, 0, $limit ) . "\n…[truncated]" : $text;
	}

	public static function excerpt( string $html, int $words = 40 ): string {
		return wp_trim_words( wp_strip_all_tags( strip_shortcodes( $html ) ), $words, '…' );
	}

	/**
	 * Generated HTML is always filtered, even for users allowed to post unfiltered HTML.
	 */
	public static function clean_html( string $html ): string {
		return wp_kses_post( $html );
	}

	public static function post( int $id ): \WP_Post|\WP_Error {
		$post = get_post( $id );
		if ( ! $post instanceof \WP_Post || ! self::allowed_type( $post->post_type ) ) {
			return new \WP_Error( 'not_found', __( 'No content with that ID exists.', 'mindio-magic-ai' ) );
		}
		return $post;
	}

	/**
	 * Posts, pages, products, and other types with an admin screen. Internal types such as privacy requests
	 * often fall back to the post capabilities, so a capability check alone would expose them.
	 */
	public static function allowed_type( string $type ): bool {
		if ( in_array( $type, array( 'post', 'page', 'product' ), true ) ) {
			return true;
		}
		$blocked = array( 'revision', 'attachment', 'nav_menu_item', 'customize_changeset', 'custom_css', 'oembed_cache', 'user_request', 'wp_block', 'wp_global_styles', 'wp_template', 'wp_template_part', 'wp_navigation', 'wp_font_family', 'wp_font_face', 'shop_order', 'shop_order_refund', 'shop_order_placehold', 'shop_coupon', 'shop_webhook', 'product_variation', 'scheduled-action' );
		if ( in_array( $type, $blocked, true ) ) {
			return false;
		}
		$object = get_post_type_object( $type );
		return $object instanceof \WP_Post_Type && $object->show_ui && ! $object->_builtin;
	}

	public static function can_edit( int $id ): bool|\WP_Error {
		$post = self::post( $id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		return current_user_can( 'edit_post', $id ) ? true : new \WP_Error( 'forbidden', __( 'You cannot edit this content.', 'mindio-magic-ai' ) );
	}

	/**
	 * A content hash and modification time that change whenever the post changes.
	 */
	public static function post_fingerprint( int $id ): string {
		$post = get_post( $id );
		if ( ! $post instanceof \WP_Post ) {
			return 'missing';
		}
		return $post->post_modified_gmt . ':' . md5( $post->post_title . "\0" . $post->post_content . "\0" . $post->post_excerpt . "\0" . $post->post_status . "\0" . $post->post_name );
	}

	/**
	 * Content written by someone who cannot edit others' posts is treated as untrusted input.
	 */
	public static function untrusted_author( \WP_Post $post ): bool {
		$author = (int) $post->post_author;
		return $author !== get_current_user_id() && ! user_can( $author, 'edit_others_posts' );
	}

	/**
	 * @param array<string,mixed> $fields
	 * @return array<int,array{field:string,label:string,before:mixed,after:mixed}>
	 */
	public static function diff( array $before, array $fields, array $labels ): array {
		$changes = array();
		foreach ( $fields as $field => $after ) {
			$old = $before[ $field ] ?? '';
			if ( (string) wp_json_encode( $old ) === (string) wp_json_encode( $after ) ) {
				continue;
			}
			$changes[] = array(
				'field'  => $field,
				'label'  => $labels[ $field ] ?? $field,
				'before' => is_string( $old ) ? self::clip( $old, 4000 ) : $old,
				'after'  => is_string( $after ) ? self::clip( $after, 4000 ) : $after,
			);
		}
		return $changes;
	}

	/** @return array<string,mixed> */
	public static function target( \WP_Post $post ): array {
		return array(
			'type'      => $post->post_type,
			'id'        => $post->ID,
			'title'     => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
			'edit_link' => (string) get_edit_post_link( $post->ID, 'raw' ),
			'link'      => (string) get_permalink( $post ),
		);
	}

	public static function limit( mixed $value, int $default, int $max ): int {
		$value = (int) $value;
		return $value > 0 ? min( $max, $value ) : $default;
	}

	/**
	 * Resolve term names or slugs to IDs. Missing terms are created only when the user may manage them.
	 *
	 * @param array<int,string> $names
	 * @return array<int,int>|\WP_Error
	 */
	public static function term_ids( array $names, string $taxonomy, bool $create ): array|\WP_Error {
		$ids = array();
		foreach ( $names as $name ) {
			$name = trim( sanitize_text_field( (string) $name ) );
			if ( '' === $name ) {
				continue;
			}
			$term = get_term_by( 'name', $name, $taxonomy ) ?: get_term_by( 'slug', sanitize_title( $name ), $taxonomy );
			if ( $term instanceof \WP_Term ) {
				$ids[] = (int) $term->term_id;
				continue;
			}
			$tax = get_taxonomy( $taxonomy );
			if ( ! $create || ! $tax || ! current_user_can( $tax->cap->edit_terms ) ) {
				return new \WP_Error(
					'unknown_term',
					/* translators: 1: term name, 2: taxonomy name. */
					sprintf( __( 'The term "%1$s" does not exist in %2$s.', 'mindio-magic-ai' ), $name, $taxonomy )
				);
			}
			$created = wp_insert_term( $name, $taxonomy );
			if ( is_wp_error( $created ) ) {
				return $created;
			}
			$ids[] = (int) $created['term_id'];
		}
		return array_values( array_unique( $ids ) );
	}
}
