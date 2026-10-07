<?php
/**
 * Revertible journal of everything one applied proposal changed.
 *
 * Adapted from Mindio Magic MCP. Before undoing, every entry is compared with
 * the live state: if someone edited the content after the AI change, undo
 * stops and reports the conflict instead of overwriting the newer edit.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Agent;

use MindioMagicAI\Core\Installer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Changeset {
	public const STATUS_OPEN     = 'open';
	public const STATUS_CLOSED   = 'closed';
	public const STATUS_REVERTED = 'reverted';

	private const MAX_ENTRIES = 2000;

	/** @return array<string,mixed> */
	public function begin( string $label, int $user_id ): array {
		global $wpdb;

		$changeset_id = 'cs_' . bin2hex( random_bytes( 10 ) );
		$now          = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned journal table.
		$wpdb->insert(
			Installer::table( 'changesets' ),
			array(
				'changeset_id' => $changeset_id,
				'label'        => mb_substr( sanitize_text_field( $label ), 0, 190 ),
				'status'       => self::STATUS_OPEN,
				'user_id'      => $user_id,
				'created_at'   => $now,
				'updated_at'   => $now,
			),
			array( '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		return (array) $this->get( $changeset_id );
	}

	/**
	 * @param array<int,array<string,mixed>> $entries
	 */
	public function record( string $changeset_id, string $tool, array $entries ): int {
		global $wpdb;

		// Options (including transients other plugins touch as a side effect) are not part of AI changes.
		$entries = array_values( array_filter( $entries, static fn( array $entry ): bool => ChangeRecorder::KIND_OPTION !== $entry['kind'] && ChangeRecorder::KIND_USER !== $entry['kind'] ) );

		$written = 0;
		foreach ( array_slice( $entries, 0, self::MAX_ENTRIES ) as $entry ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned journal table.
			$wpdb->insert(
				Installer::table( 'changeset_items' ),
				array(
					'changeset_id' => $changeset_id,
					'tool'         => sanitize_key( $tool ),
					'kind'         => sanitize_key( (string) $entry['kind'] ),
					'object_key'   => mb_substr( (string) $entry['key'], 0, 190 ),
					'operation'    => sanitize_key( (string) $entry['operation'] ),
					'target'       => (string) wp_json_encode( $entry['target'] ),
					'before_state' => (string) wp_json_encode( $entry['before'] ),
					'after_state'  => (string) wp_json_encode( $entry['after'] ),
					'created_at'   => current_time( 'mysql', true ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
			++$written;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned journal table.
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE %i SET entries = entries + %d, status = %s, updated_at = %s WHERE changeset_id = %s',
				Installer::table( 'changesets' ),
				$written,
				self::STATUS_CLOSED,
				current_time( 'mysql', true ),
				$changeset_id
			)
		);

		return $written;
	}

	/** @return array<string,mixed>|null */
	public function get( string $changeset_id ): ?array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Journal reads must be current.
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE changeset_id = %s', Installer::table( 'changesets' ), $changeset_id ), ARRAY_A );
		if ( ! is_array( $row ) ) {
			return null;
		}
		$row['id']      = (int) $row['id'];
		$row['user_id'] = (int) $row['user_id'];
		$row['entries'] = (int) $row['entries'];
		return $row;
	}

	/** @return array<int,array<string,mixed>> */
	public function entries( string $changeset_id ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Journal reads must be current.
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE changeset_id = %s ORDER BY id DESC', Installer::table( 'changeset_items' ), $changeset_id ), ARRAY_A );
		return array_map(
			static function ( array $row ): array {
				$row['id']           = (int) $row['id'];
				$row['target']       = json_decode( (string) $row['target'], true );
				$row['before_state'] = json_decode( (string) $row['before_state'], true );
				$row['after_state']  = json_decode( (string) $row['after_state'], true );
				return $row;
			},
			(array) $rows
		);
	}

	/**
	 * Entries whose live state no longer matches what the AI change left behind.
	 *
	 * @param array<int,array<string,mixed>> $entries
	 * @return array<int,array{kind:string,key:string}>
	 */
	public function conflicts( array $entries ): array {
		$recorder  = new ChangeRecorder();
		$conflicts = array();
		foreach ( $entries as $entry ) {
			$current = $recorder->read_state( (string) $entry['kind'], array( 'target' => (array) $entry['target'] ) );
			if ( self::normalize( $current ) !== self::normalize( $entry['after_state'] ) ) {
				$conflicts[] = array(
					'kind' => (string) $entry['kind'],
					'key'  => (string) $entry['object_key'],
				);
			}
		}
		return $conflicts;
	}

	/**
	 * Undo every entry, newest first. Without $force, a conflict aborts the whole undo.
	 *
	 * @return array<string,mixed>|\WP_Error
	 */
	public function revert( string $changeset_id, bool $force = false ): array|\WP_Error {
		$changeset = $this->get( $changeset_id );
		if ( ! $changeset ) {
			return new \WP_Error( 'not_found', __( 'Unknown change record.', 'mindio-magic-ai' ), array( 'status' => 404 ) );
		}
		if ( self::STATUS_REVERTED === $changeset['status'] ) {
			return new \WP_Error( 'already_reverted', __( 'This change has already been undone.', 'mindio-magic-ai' ), array( 'status' => 409 ) );
		}

		$entries   = $this->entries( $changeset_id );
		$conflicts = $this->conflicts( $entries );
		if ( $conflicts && ! $force ) {
			return new \WP_Error(
				'undo_conflict',
				__( 'This content was edited after the AI change. Undoing now would overwrite those edits.', 'mindio-magic-ai' ),
				array(
					'status'    => 409,
					'conflicts' => $conflicts,
				)
			);
		}

		$reverted = 0;
		$skipped  = array();
		$products = array();
		foreach ( $entries as $entry ) {
			$outcome = $this->revert_entry( $entry );
			if ( is_wp_error( $outcome ) ) {
				$skipped[] = array(
					'kind'   => (string) $entry['kind'],
					'key'    => (string) $entry['object_key'],
					'reason' => $outcome->get_error_message(),
				);
				continue;
			}
			if ( $outcome ) {
				++$reverted;
			}
			if ( ChangeRecorder::KIND_PRODUCT === $entry['kind'] ) {
				$products[] = (int) $entry['target']['product_id'];
			}
		}

		// Product data also lives in lookup tables and transients that only WooCommerce CRUD keeps in sync.
		if ( function_exists( 'wc_get_product' ) ) {
			foreach ( array_unique( $products ) as $product_id ) {
				$product = wc_get_product( $product_id );
				if ( $product instanceof \WC_Product ) {
					$product->save();
					wc_delete_product_transients( $product_id );
				}
			}
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned journal table.
		$wpdb->update(
			Installer::table( 'changesets' ),
			array(
				'status'     => self::STATUS_REVERTED,
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'changeset_id' => $changeset_id )
		);

		return array(
			'changeset_id' => $changeset_id,
			'reverted'     => $reverted,
			'skipped'      => $skipped,
			'forced'       => $force && (bool) $conflicts,
		);
	}

	/** @param array<string,mixed> $entry */
	private function revert_entry( array $entry ): bool|\WP_Error {
		$target = (array) $entry['target'];
		$before = $entry['before_state'];
		$after  = $entry['after_state'];

		return match ( (string) $entry['kind'] ) {
			ChangeRecorder::KIND_POST         => $this->revert_post( (int) $target['post_id'], $before, $after ),
			ChangeRecorder::KIND_META         => $this->revert_meta( (int) $target['post_id'], (string) $target['meta_key'], $before ),
			ChangeRecorder::KIND_OBJECT_TERMS => $this->revert_object_terms( (int) $target['object_id'], (string) $target['taxonomy'], (array) $before ),
			ChangeRecorder::KIND_TERM         => $this->revert_term( (int) $target['term_id'], $before, $after ),
			ChangeRecorder::KIND_COMMENT      => $this->revert_comment( (int) $target['comment_id'], $before, $after ),
			ChangeRecorder::KIND_PRODUCT      => $this->revert_product( (int) $target['product_id'], $before ),
			default                           => new \WP_Error( 'unsupported', __( 'This kind of change cannot be undone automatically.', 'mindio-magic-ai' ) ),
		};
	}

	private function revert_post( int $post_id, mixed $before, mixed $after ): bool|\WP_Error {
		if ( null === $before && is_array( $after ) ) {
			// A post the AI created goes to the trash, not permanent deletion, so it stays recoverable.
			if ( ! current_user_can( 'delete_post', $post_id ) ) {
				return new \WP_Error( 'forbidden', __( 'You cannot delete the post this change created.', 'mindio-magic-ai' ) );
			}
			return (bool) wp_trash_post( $post_id );
		}
		if ( ! is_array( $before ) ) {
			return false;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'forbidden', __( 'You cannot edit the post this change modified.', 'mindio-magic-ai' ) );
		}
		if ( null === $after && 'trash' !== get_post_status( $post_id ) ) {
			return false;
		}
		if ( 'trash' === get_post_status( $post_id ) && 'trash' !== ( $before['post_status'] ?? '' ) ) {
			wp_untrash_post( $post_id );
		}

		$update = array( 'ID' => $post_id );
		foreach ( array( 'post_title', 'post_name', 'post_status', 'post_excerpt', 'post_content', 'post_parent', 'menu_order' ) as $field ) {
			if ( array_key_exists( $field, $before ) ) {
				$update[ $field ] = $before[ $field ];
			}
		}
		if ( 'future' === ( $before['post_status'] ?? '' ) || 'future' === get_post_status( $post_id ) ) {
			$update['edit_date'] = true;
		}

		return ! is_wp_error( wp_update_post( wp_slash( $update ), true ) );
	}

	private function revert_meta( int $post_id, string $meta_key, mixed $before ): bool|\WP_Error {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'forbidden', __( 'You cannot edit the post this change modified.', 'mindio-magic-ai' ) );
		}
		if ( null === $before || '' === $before ) {
			return (bool) delete_post_meta( $post_id, $meta_key );
		}
		return false !== update_post_meta( $post_id, $meta_key, wp_slash( $before ) );
	}

	/** @param array<int,string> $before */
	private function revert_object_terms( int $object_id, string $taxonomy, array $before ): bool|\WP_Error {
		if ( ! current_user_can( 'edit_post', $object_id ) ) {
			return new \WP_Error( 'forbidden', __( 'You cannot edit the post this change modified.', 'mindio-magic-ai' ) );
		}
		return ! is_wp_error( wp_set_object_terms( $object_id, $before, $taxonomy, false ) );
	}

	private function revert_term( int $term_id, mixed $before, mixed $after ): bool|\WP_Error {
		if ( ! current_user_can( 'manage_categories' ) ) {
			return new \WP_Error( 'forbidden', __( 'You cannot manage terms.', 'mindio-magic-ai' ) );
		}
		if ( null === $before && is_array( $after ) ) {
			$taxonomy = (string) ( $after['taxonomy'] ?? '' );
			return '' !== $taxonomy && true === wp_delete_term( $term_id, $taxonomy );
		}
		if ( ! is_array( $before ) || empty( $before['taxonomy'] ) ) {
			return false;
		}
		$result = wp_update_term(
			$term_id,
			(string) $before['taxonomy'],
			array(
				'name'        => (string) $before['name'],
				'slug'        => (string) $before['slug'],
				'parent'      => (int) $before['parent'],
				'description' => (string) $before['description'],
			)
		);
		return ! is_wp_error( $result );
	}

	private function revert_comment( int $comment_id, mixed $before, mixed $after ): bool|\WP_Error {
		if ( ! current_user_can( 'moderate_comments' ) ) {
			return new \WP_Error( 'forbidden', __( 'You cannot moderate comments.', 'mindio-magic-ai' ) );
		}
		if ( null === $before && is_array( $after ) ) {
			return (bool) wp_trash_comment( $comment_id );
		}
		if ( ! is_array( $before ) ) {
			return false;
		}
		$status = (string) $before['comment_approved'];
		wp_set_comment_status( $comment_id, match ( $status ) {
			'1'     => 'approve',
			'0'     => 'hold',
			default => $status,
		} );
		return (bool) wp_update_comment(
			array(
				'comment_ID'      => $comment_id,
				'comment_content' => (string) $before['comment_content'],
			)
		);
	}

	private function revert_product( int $product_id, mixed $before ): bool|\WP_Error {
		if ( ! function_exists( 'wc_get_product' ) || ! is_array( $before ) ) {
			return false;
		}
		if ( ! current_user_can( 'edit_product', $product_id ) ) {
			return new \WP_Error( 'forbidden', __( 'You cannot edit this product.', 'mindio-magic-ai' ) );
		}
		$product = wc_get_product( $product_id );
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}
		$product->set_name( (string) $before['name'] );
		$product->set_status( (string) $before['status'] );
		$product->set_description( (string) $before['description'] );
		$product->set_short_description( (string) $before['short_description'] );
		$product->set_regular_price( (string) $before['regular_price'] );
		$product->set_sale_price( (string) $before['sale_price'] );
		$product->set_category_ids( (array) $before['category_ids'] );
		$product->set_tag_ids( (array) $before['tag_ids'] );
		try {
			$product->set_sku( (string) $before['sku'] );
		} catch ( \Throwable ) {
			// A SKU taken by another product since then is left unchanged.
			unset( $before['sku'] );
		}
		$product->save();
		return true;
	}

	private static function normalize( mixed $value ): string {
		return (string) wp_json_encode( json_decode( (string) wp_json_encode( $value ), true ) );
	}
}
