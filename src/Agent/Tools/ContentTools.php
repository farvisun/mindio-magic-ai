<?php
/**
 * Posts and pages: search, read, draft, update, schedule, publish, and terms.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Agent\Tools;

use MindioMagicAI\Agent\Tool;
use MindioMagicAI\Agent\ToolRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ContentTools {
	private const EDITABLE_TYPES = array( 'post', 'page' );

	public static function register( ToolRegistry $registry ): void {
		$registry->register(
			new Tool(
				name: 'search_content',
				label: __( 'Search content', 'mindio-magic-ai' ),
				description: 'Search posts, pages, and products by keyword and status. Returns IDs, titles, status, dates, and links. Use get_content to read one item in full.',
				schema: array(
					'type'                 => 'object',
					'properties'           => array(
						'query'     => array(
							'type'        => 'string',
							'description' => 'Keywords. Leave empty to list the most recent items.',
						),
						'post_type' => array(
							'type' => 'string',
							'enum' => array( 'post', 'page', 'product', 'any' ),
						),
						'status'    => array(
							'type' => 'string',
							'enum' => array( 'publish', 'draft', 'pending', 'future', 'private', 'any' ),
						),
						'limit'     => array(
							'type'        => 'integer',
							'description' => 'Between 1 and 25. Defaults to 10.',
						),
					),
					'additionalProperties' => false,
				),
				risk: Tool::RISK_READ,
				permission: static fn(): bool => current_user_can( 'edit_posts' ),
				handler: array( self::class, 'search' ),
			)
		);

		$registry->register(
			new Tool(
				name: 'get_content',
				label: __( 'Read content', 'mindio-magic-ai' ),
				description: 'Read one post or page in full: title, status, slug, excerpt, content (block markup or HTML), categories, tags, author, and word count.',
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
			)
		);

		$registry->register(
			new Tool(
				name: 'list_terms',
				label: __( 'List categories and tags', 'mindio-magic-ai' ),
				description: 'List terms of a taxonomy (category, post_tag, product_cat, product_tag) with their post counts.',
				schema: array(
					'type'                 => 'object',
					'properties'           => array(
						'taxonomy' => array(
							'type' => 'string',
							'enum' => array( 'category', 'post_tag', 'product_cat', 'product_tag' ),
						),
						'search'   => array( 'type' => 'string' ),
						'limit'    => array( 'type' => 'integer' ),
					),
					'required'             => array( 'taxonomy' ),
					'additionalProperties' => false,
				),
				risk: Tool::RISK_READ,
				permission: static fn(): bool => current_user_can( 'edit_posts' ),
				handler: array( self::class, 'terms' ),
			)
		);

		$registry->register(
			new Tool(
				name: 'create_draft',
				label: __( 'Create a draft', 'mindio-magic-ai' ),
				description: 'Create a new post or page as a draft. Nothing is published. Content may be HTML or WordPress block markup. Categories must already exist; tags are created when missing. The user reviews the draft before it is created.',
				schema: array(
					'type'                 => 'object',
					'properties'           => array(
						'post_type'  => array(
							'type' => 'string',
							'enum' => self::EDITABLE_TYPES,
						),
						'title'      => array( 'type' => 'string' ),
						'content'    => array(
							'type'        => 'string',
							'description' => 'Full body as HTML or block markup.',
						),
						'excerpt'    => array( 'type' => 'string' ),
						'categories' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'tags'       => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
					'required'             => array( 'post_type', 'title', 'content' ),
					'additionalProperties' => false,
				),
				risk: Tool::RISK_WRITE,
				permission: static function ( array $args ): bool|\WP_Error {
					$type = get_post_type_object( (string) ( $args['post_type'] ?? 'post' ) );
					return $type && current_user_can( $type->cap->edit_posts ) ? true : new \WP_Error( 'forbidden', __( 'You cannot create this kind of content.', 'mindio-magic-ai' ) );
				},
				handler: array( self::class, 'create_draft' ),
				preview: array( self::class, 'preview_create' ),
				reversible: true,
				auto_apply: true,
			)
		);

		$registry->register(
			new Tool(
				name: 'update_content',
				label: __( 'Edit content', 'mindio-magic-ai' ),
				description: 'Change the title, content, excerpt, or slug of an existing post or page. Send only the fields to change; content replaces the whole body, so include the complete new body. The status is not changed. The user reviews a before/after diff first.',
				schema: array(
					'type'                 => 'object',
					'properties'           => array(
						'id'      => array( 'type' => 'integer' ),
						'title'   => array( 'type' => 'string' ),
						'content' => array( 'type' => 'string' ),
						'excerpt' => array( 'type' => 'string' ),
						'slug'    => array( 'type' => 'string' ),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				risk: Tool::RISK_WRITE,
				permission: static fn( array $args ): bool|\WP_Error => Support::can_edit( (int) $args['id'] ),
				handler: array( self::class, 'update' ),
				preview: array( self::class, 'preview_update' ),
				fingerprint: static fn( array $args ): string => Support::post_fingerprint( (int) $args['id'] ),
				reversible: true,
			)
		);

		$registry->register(
			new Tool(
				name: 'schedule_content',
				label: __( 'Schedule content', 'mindio-magic-ai' ),
				description: 'Schedule a draft or pending post or page to publish at a future date and time in the site timezone (ISO 8601, for example 2026-11-01T09:00:00).',
				schema: array(
					'type'                 => 'object',
					'properties'           => array(
						'id'   => array( 'type' => 'integer' ),
						'date' => array( 'type' => 'string' ),
					),
					'required'             => array( 'id', 'date' ),
					'additionalProperties' => false,
				),
				risk: Tool::RISK_WRITE,
				permission: static fn( array $args ): bool|\WP_Error => self::can_publish( (int) $args['id'] ),
				handler: array( self::class, 'schedule' ),
				preview: array( self::class, 'preview_schedule' ),
				fingerprint: static fn( array $args ): string => Support::post_fingerprint( (int) $args['id'] ),
				reversible: true,
			)
		);

		$registry->register(
			new Tool(
				name: 'publish_content',
				label: __( 'Publish content', 'mindio-magic-ai' ),
				description: 'Publish a draft, pending, or scheduled post or page now. Only use this when the user explicitly asks to publish.',
				schema: array(
					'type'                 => 'object',
					'properties'           => array(
						'id' => array( 'type' => 'integer' ),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				risk: Tool::RISK_WRITE,
				permission: static fn( array $args ): bool|\WP_Error => self::can_publish( (int) $args['id'] ),
				handler: array( self::class, 'publish' ),
				preview: array( self::class, 'preview_publish' ),
				fingerprint: static fn( array $args ): string => Support::post_fingerprint( (int) $args['id'] ),
				reversible: true,
			)
		);

		$registry->register(
			new Tool(
				name: 'set_terms',
				label: __( 'Set categories or tags', 'mindio-magic-ai' ),
				description: 'Replace (or with append=true, add to) the categories or tags of a post or product. Categories must exist; tags are created when missing.',
				schema: array(
					'type'                 => 'object',
					'properties'           => array(
						'id'       => array( 'type' => 'integer' ),
						'taxonomy' => array(
							'type' => 'string',
							'enum' => array( 'category', 'post_tag', 'product_cat', 'product_tag' ),
						),
						'terms'    => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'append'   => array( 'type' => 'boolean' ),
					),
					'required'             => array( 'id', 'taxonomy', 'terms' ),
					'additionalProperties' => false,
				),
				risk: Tool::RISK_WRITE,
				permission: static function ( array $args ): bool|\WP_Error {
					$editable = Support::can_edit( (int) $args['id'] );
					if ( true !== $editable ) {
						return $editable;
					}
					$tax = get_taxonomy( (string) $args['taxonomy'] );
					return $tax && current_user_can( $tax->cap->assign_terms ) ? true : new \WP_Error( 'forbidden', __( 'You cannot assign these terms.', 'mindio-magic-ai' ) );
				},
				handler: array( self::class, 'set_terms' ),
				preview: array( self::class, 'preview_terms' ),
				fingerprint: static fn( array $args ): string => Support::post_fingerprint( (int) $args['id'] ) . ':' . implode( ',', wp_get_object_terms( (int) $args['id'], (string) $args['taxonomy'], array( 'fields' => 'ids' ) ) ?: array() ),
				reversible: true,
			)
		);
	}

	/** @return array<string,mixed> */
	public static function search( array $args ): array {
		$type   = (string) ( $args['post_type'] ?? 'any' );
		$status = (string) ( $args['status'] ?? 'any' );
		$types  = 'any' === $type ? array_values( array_filter( array( 'post', 'page', 'product' ), 'post_type_exists' ) ) : array( $type );
		if ( 'product' === $type && ! post_type_exists( 'product' ) ) {
			return array( 'items' => array() );
		}

		$query = new \WP_Query(
			array(
				's'                   => sanitize_text_field( (string) ( $args['query'] ?? '' ) ),
				'post_type'           => $types,
				'post_status'         => 'any' === $status ? array( 'publish', 'draft', 'pending', 'future', 'private' ) : array( $status ),
				'posts_per_page'      => Support::limit( $args['limit'] ?? 10, 10, 25 ),
				'orderby'             => '' === (string) ( $args['query'] ?? '' ) ? 'modified' : 'relevance',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => false,
			)
		);

		$items = array();
		foreach ( $query->posts as $post ) {
			if ( $post instanceof \WP_Post && current_user_can( 'edit_post', $post->ID ) ) {
				$items[] = Support::post_summary( $post ) + array( 'excerpt' => Support::excerpt( $post->post_excerpt ?: $post->post_content, 30 ) );
			}
		}

		return array(
			'total' => (int) $query->found_posts,
			'items' => $items,
		);
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function get( array $args ): array|\WP_Error {
		$post = Support::post( (int) $args['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		$author = get_userdata( (int) $post->post_author );

		return Support::post_summary( $post ) + array(
			'slug'        => $post->post_name,
			'excerpt'     => $post->post_excerpt,
			'content'     => Support::clip( $post->post_content ),
			'word_count'  => str_word_count( wp_strip_all_tags( $post->post_content ) ) ?: count( preg_split( '/\s+/u', trim( wp_strip_all_tags( $post->post_content ) ) ) ?: array() ),
			'author'      => $author instanceof \WP_User ? $author->display_name : '',
			'categories'  => wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) ),
			'tags'        => wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) ),
			'_untrusted'  => Support::untrusted_author( $post ),
		);
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function terms( array $args ): array|\WP_Error {
		$taxonomy = (string) $args['taxonomy'];
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'unknown_taxonomy', __( 'That taxonomy is not available on this site.', 'mindio-magic-ai' ) );
		}
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'search'     => sanitize_text_field( (string) ( $args['search'] ?? '' ) ),
				'number'     => Support::limit( $args['limit'] ?? 50, 50, 200 ),
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);
		if ( is_wp_error( $terms ) ) {
			return $terms;
		}
		return array(
			'items' => array_map(
				static fn( \WP_Term $term ): array => array(
					'id'     => $term->term_id,
					'name'   => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
					'slug'   => $term->slug,
					'count'  => $term->count,
					'parent' => $term->parent,
				),
				$terms
			),
		);
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function preview_create( array $args ): array|\WP_Error {
		$categories = Support::term_ids( (array) ( $args['categories'] ?? array() ), 'category', false );
		if ( 'post' === $args['post_type'] && is_wp_error( $categories ) ) {
			return $categories;
		}
		$type = get_post_type_object( (string) $args['post_type'] );
		return array(
			'summary' => sprintf(
				/* translators: 1: content type, 2: title. */
				__( 'Create a draft %1$s: “%2$s”', 'mindio-magic-ai' ),
				$type ? mb_strtolower( (string) $type->labels->singular_name ) : (string) $args['post_type'],
				sanitize_text_field( (string) $args['title'] )
			),
			'changes' => array_values(
				array_filter(
					array(
						array( 'field' => 'title', 'label' => __( 'Title', 'mindio-magic-ai' ), 'before' => '', 'after' => sanitize_text_field( (string) $args['title'] ) ),
						array( 'field' => 'excerpt', 'label' => __( 'Excerpt', 'mindio-magic-ai' ), 'before' => '', 'after' => (string) ( $args['excerpt'] ?? '' ) ),
						array( 'field' => 'content', 'label' => __( 'Content', 'mindio-magic-ai' ), 'before' => '', 'after' => Support::clip( Support::clean_html( (string) $args['content'] ), 4000 ) ),
						array( 'field' => 'categories', 'label' => __( 'Categories', 'mindio-magic-ai' ), 'before' => '', 'after' => implode( ', ', (array) ( $args['categories'] ?? array() ) ) ),
						array( 'field' => 'tags', 'label' => __( 'Tags', 'mindio-magic-ai' ), 'before' => '', 'after' => implode( ', ', (array) ( $args['tags'] ?? array() ) ) ),
					),
					static fn( array $change ): bool => '' !== $change['after']
				)
			),
		);
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function create_draft( array $args ): array|\WP_Error {
		$type = (string) $args['post_type'];
		if ( ! in_array( $type, self::EDITABLE_TYPES, true ) ) {
			return new \WP_Error( 'invalid_type', __( 'Only posts and pages can be drafted here.', 'mindio-magic-ai' ) );
		}
		$categories = 'post' === $type ? Support::term_ids( (array) ( $args['categories'] ?? array() ), 'category', false ) : array();
		if ( is_wp_error( $categories ) ) {
			return $categories;
		}

		$post_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'     => $type,
					'post_status'   => 'draft',
					'post_title'    => sanitize_text_field( (string) $args['title'] ),
					'post_content'  => Support::clean_html( (string) $args['content'] ),
					'post_excerpt'  => sanitize_textarea_field( (string) ( $args['excerpt'] ?? '' ) ),
					'post_author'   => get_current_user_id(),
					'post_category' => $categories ?: array(),
				)
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
		if ( 'post' === $type && ! empty( $args['tags'] ) ) {
			wp_set_post_tags( $post_id, array_map( 'sanitize_text_field', (array) $args['tags'] ), false );
		}

		$post = get_post( $post_id );
		return array(
			'created' => $post instanceof \WP_Post ? Support::post_summary( $post ) : array( 'id' => $post_id ),
			'target'  => $post instanceof \WP_Post ? Support::target( $post ) : array(),
		);
	}

	/** @return array<string,mixed> */
	private static function labels(): array {
		return array(
			'post_title'   => __( 'Title', 'mindio-magic-ai' ),
			'post_content' => __( 'Content', 'mindio-magic-ai' ),
			'post_excerpt' => __( 'Excerpt', 'mindio-magic-ai' ),
			'post_name'    => __( 'Slug', 'mindio-magic-ai' ),
			'post_status'  => __( 'Status', 'mindio-magic-ai' ),
			'post_date'    => __( 'Publish date', 'mindio-magic-ai' ),
		);
	}

	/** @return array<string,mixed> */
	private static function update_fields( array $args ): array {
		$fields = array();
		if ( isset( $args['title'] ) ) {
			$fields['post_title'] = sanitize_text_field( (string) $args['title'] );
		}
		if ( isset( $args['content'] ) ) {
			$fields['post_content'] = Support::clean_html( (string) $args['content'] );
		}
		if ( isset( $args['excerpt'] ) ) {
			$fields['post_excerpt'] = sanitize_textarea_field( (string) $args['excerpt'] );
		}
		if ( isset( $args['slug'] ) ) {
			$fields['post_name'] = sanitize_title( (string) $args['slug'] );
		}
		return $fields;
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function preview_update( array $args ): array|\WP_Error {
		$post = Support::post( (int) $args['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		$fields = self::update_fields( $args );
		if ( ! $fields ) {
			return new \WP_Error( 'nothing_to_change', __( 'No fields to change were provided.', 'mindio-magic-ai' ) );
		}
		$changes = Support::diff( $post->to_array(), $fields, self::labels() );
		if ( ! $changes ) {
			return new \WP_Error( 'nothing_to_change', __( 'The proposed values match the current content.', 'mindio-magic-ai' ) );
		}
		return array(
			'summary' => sprintf(
				/* translators: %s: content title. */
				__( 'Edit “%s”', 'mindio-magic-ai' ),
				html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' )
			),
			'target'  => Support::target( $post ),
			'changes' => $changes,
		);
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function update( array $args ): array|\WP_Error {
		$fields = self::update_fields( $args );
		$result = wp_update_post( wp_slash( array( 'ID' => (int) $args['id'] ) + $fields ), true );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$post = get_post( (int) $args['id'] );
		return array(
			'updated' => $post instanceof \WP_Post ? Support::post_summary( $post ) : array(),
			'target'  => $post instanceof \WP_Post ? Support::target( $post ) : array(),
		);
	}

	private static function can_publish( int $id ): bool|\WP_Error {
		$post = Support::post( $id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		$type = get_post_type_object( $post->post_type );
		return $type && current_user_can( 'edit_post', $id ) && current_user_can( $type->cap->publish_posts ) ? true : new \WP_Error( 'forbidden', __( 'You cannot publish this content.', 'mindio-magic-ai' ) );
	}

	private static function local_date( string $date ): string|\WP_Error {
		try {
			$parsed = new \DateTimeImmutable( $date, wp_timezone() );
		} catch ( \Exception ) {
			return new \WP_Error( 'invalid_date', __( 'The date is not valid.', 'mindio-magic-ai' ) );
		}
		$local = $parsed->setTimezone( wp_timezone() );
		if ( $local->getTimestamp() <= time() ) {
			return new \WP_Error( 'date_in_past', __( 'The schedule date must be in the future.', 'mindio-magic-ai' ) );
		}
		return $local->format( 'Y-m-d H:i:s' );
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function preview_schedule( array $args ): array|\WP_Error {
		$post = Support::post( (int) $args['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		if ( 'publish' === $post->post_status ) {
			return new \WP_Error( 'already_published', __( 'This content is already published.', 'mindio-magic-ai' ) );
		}
		$date = self::local_date( (string) $args['date'] );
		if ( is_wp_error( $date ) ) {
			return $date;
		}
		return array(
			'summary' => sprintf(
				/* translators: 1: content title, 2: date and time. */
				__( 'Schedule “%1$s” for %2$s', 'mindio-magic-ai' ),
				html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
				wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date . ' ' . wp_timezone_string() ) ?: null )
			),
			'target'  => Support::target( $post ),
			'changes' => Support::diff( $post->to_array(), array( 'post_status' => 'future', 'post_date' => $date ), self::labels() ),
		);
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function schedule( array $args ): array|\WP_Error {
		$date = self::local_date( (string) $args['date'] );
		if ( is_wp_error( $date ) ) {
			return $date;
		}
		$result = wp_update_post(
			array(
				'ID'            => (int) $args['id'],
				'post_status'   => 'future',
				'post_date'     => $date,
				'post_date_gmt' => get_gmt_from_date( $date ),
				'edit_date'     => true,
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$post = get_post( (int) $args['id'] );
		return array(
			'scheduled' => $post instanceof \WP_Post ? Support::post_summary( $post ) : array(),
			'target'    => $post instanceof \WP_Post ? Support::target( $post ) : array(),
		);
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function preview_publish( array $args ): array|\WP_Error {
		$post = Support::post( (int) $args['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		if ( 'publish' === $post->post_status ) {
			return new \WP_Error( 'already_published', __( 'This content is already published.', 'mindio-magic-ai' ) );
		}
		return array(
			'summary' => sprintf(
				/* translators: %s: content title. */
				__( 'Publish “%s” now', 'mindio-magic-ai' ),
				html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' )
			),
			'target'  => Support::target( $post ),
			'changes' => Support::diff( $post->to_array(), array( 'post_status' => 'publish' ), self::labels() ),
			'warning' => __( 'Visitors will be able to see this content as soon as it is published.', 'mindio-magic-ai' ),
		);
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function publish( array $args ): array|\WP_Error {
		$id   = (int) $args['id'];
		$post = Support::post( $id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		$update = array(
			'ID'          => $id,
			'post_status' => 'publish',
		);
		if ( 'future' === $post->post_status ) {
			$update['post_date']     = current_time( 'mysql' );
			$update['post_date_gmt'] = current_time( 'mysql', true );
			$update['edit_date']     = true;
		}
		$result = wp_update_post( $update, true );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$post = get_post( $id );
		return array(
			'published' => $post instanceof \WP_Post ? Support::post_summary( $post ) : array(),
			'target'    => $post instanceof \WP_Post ? Support::target( $post ) : array(),
		);
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function preview_terms( array $args ): array|\WP_Error {
		$post = Support::post( (int) $args['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		$taxonomy = (string) $args['taxonomy'];
		if ( ! is_object_in_taxonomy( $post->post_type, $taxonomy ) ) {
			return new \WP_Error( 'invalid_taxonomy', __( 'This content does not use that taxonomy.', 'mindio-magic-ai' ) );
		}
		$hierarchical = is_taxonomy_hierarchical( $taxonomy );
		$ids          = Support::term_ids( (array) $args['terms'], $taxonomy, ! $hierarchical );
		if ( is_wp_error( $ids ) ) {
			return $ids;
		}
		$current = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'names' ) );
		$current = is_wp_error( $current ) ? array() : $current;
		$next    = ! empty( $args['append'] ) ? array_values( array_unique( array_merge( $current, array_map( 'strval', (array) $args['terms'] ) ) ) ) : array_map( 'strval', (array) $args['terms'] );
		$tax     = get_taxonomy( $taxonomy );

		return array(
			'summary' => sprintf(
				/* translators: 1: taxonomy label, 2: content title. */
				__( 'Update %1$s on “%2$s”', 'mindio-magic-ai' ),
				$tax ? mb_strtolower( (string) $tax->labels->name ) : $taxonomy,
				html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' )
			),
			'target'  => Support::target( $post ),
			'changes' => array(
				array(
					'field'  => $taxonomy,
					'label'  => $tax ? (string) $tax->labels->name : $taxonomy,
					'before' => implode( ', ', $current ),
					'after'  => implode( ', ', $next ),
				),
			),
		);
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function set_terms( array $args ): array|\WP_Error {
		$taxonomy = (string) $args['taxonomy'];
		$ids      = Support::term_ids( (array) $args['terms'], $taxonomy, ! is_taxonomy_hierarchical( $taxonomy ) );
		if ( is_wp_error( $ids ) ) {
			return $ids;
		}
		$result = wp_set_object_terms( (int) $args['id'], $ids, $taxonomy, ! empty( $args['append'] ) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$post = get_post( (int) $args['id'] );
		return array(
			'terms'  => wp_get_object_terms( (int) $args['id'], $taxonomy, array( 'fields' => 'names' ) ),
			'target' => $post instanceof \WP_Post ? Support::target( $post ) : array(),
		);
	}
}
