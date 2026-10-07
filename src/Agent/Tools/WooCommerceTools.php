<?php
/**
 * WooCommerce: product search and edits, stock, and fixed-shape sales reports.
 *
 * Reports read the WooCommerce Analytics tables (wc_order_stats and
 * wc_order_product_lookup) with fixed queries; the model never supplies SQL.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Agent\Tools;

use MindioMagicAI\Agent\Tool;
use MindioMagicAI\Agent\ToolRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WooCommerceTools {
	private const PAID_STATUSES = array( 'wc-completed', 'wc-processing', 'wc-on-hold' );

	public static function register( ToolRegistry $registry ): void {
		$can_edit_products = static fn(): bool|\WP_Error => current_user_can( 'edit_products' ) ? true : new \WP_Error( 'forbidden', __( 'You cannot manage products.', 'mindio-magic-ai' ) );
		$can_view_reports  = static fn(): bool|\WP_Error => current_user_can( 'view_woocommerce_reports' ) ? true : new \WP_Error( 'forbidden', __( 'You cannot view store reports.', 'mindio-magic-ai' ) );
		$range_schema      = array(
			'from' => array(
				'type'        => 'string',
				'description' => 'Start date, YYYY-MM-DD, site timezone.',
			),
			'to'   => array(
				'type'        => 'string',
				'description' => 'End date, YYYY-MM-DD, inclusive.',
			),
		);

		$registry->register(
			new Tool(
				name: 'search_products',
				label: __( 'Search products', 'mindio-magic-ai' ),
				description: 'Search WooCommerce products by keyword, category slug, status, or stock status. Returns price, stock, and links.',
				schema: array(
					'type'                 => 'object',
					'properties'           => array(
						'query'        => array( 'type' => 'string' ),
						'category'     => array( 'type' => 'string' ),
						'status'       => array(
							'type' => 'string',
							'enum' => array( 'publish', 'draft', 'pending', 'private', 'any' ),
						),
						'stock_status' => array(
							'type' => 'string',
							'enum' => array( 'instock', 'outofstock', 'onbackorder' ),
						),
						'limit'        => array( 'type' => 'integer' ),
					),
					'additionalProperties' => false,
				),
				risk: Tool::RISK_READ,
				permission: $can_edit_products,
				handler: array( self::class, 'search' ),
				group: 'woocommerce',
			)
		);

		$registry->register(
			new Tool(
				name: 'get_product',
				label: __( 'Read a product', 'mindio-magic-ai' ),
				description: 'Read one product in full: description, short description, prices, SKU, stock, categories, tags, attributes, rating, and total sales.',
				schema: array(
					'type'                 => 'object',
					'properties'           => array(
						'id' => array( 'type' => 'integer' ),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				risk: Tool::RISK_READ,
				permission: $can_edit_products,
				handler: array( self::class, 'get' ),
				group: 'woocommerce',
			)
		);

		$registry->register(
			new Tool(
				name: 'low_stock_products',
				label: __( 'List low-stock products', 'mindio-magic-ai' ),
				description: 'List products that manage stock and are at or below a threshold (defaults to the store’s low-stock setting).',
				schema: array(
					'type'                 => 'object',
					'properties'           => array(
						'threshold' => array( 'type' => 'integer' ),
						'limit'     => array( 'type' => 'integer' ),
					),
					'additionalProperties' => false,
				),
				risk: Tool::RISK_READ,
				permission: $can_edit_products,
				handler: array( self::class, 'low_stock' ),
				group: 'woocommerce',
			)
		);

		$registry->register(
			new Tool(
				name: 'sales_summary',
				label: __( 'Summarize sales', 'mindio-magic-ai' ),
				description: 'Orders, items sold, gross sales, net revenue, and average order value for a date range, counting completed, processing, and on-hold orders. Amounts are in the store currency.',
				schema: array(
					'type'                 => 'object',
					'properties'           => $range_schema,
					'required'             => array( 'from', 'to' ),
					'additionalProperties' => false,
				),
				risk: Tool::RISK_READ,
				permission: $can_view_reports,
				handler: array( self::class, 'sales_summary' ),
				group: 'woocommerce',
			)
		);

		$registry->register(
			new Tool(
				name: 'top_products',
				label: __( 'Rank best-selling products', 'mindio-magic-ai' ),
				description: 'Best-selling products by units and net revenue for a date range.',
				schema: array(
					'type'                 => 'object',
					'properties'           => $range_schema + array( 'limit' => array( 'type' => 'integer' ) ),
					'required'             => array( 'from', 'to' ),
					'additionalProperties' => false,
				),
				risk: Tool::RISK_READ,
				permission: $can_view_reports,
				handler: array( self::class, 'top_products' ),
				group: 'woocommerce',
			)
		);

		$registry->register(
			new Tool(
				name: 'update_product',
				label: __( 'Edit a product', 'mindio-magic-ai' ),
				description: 'Change a product’s name, description, short description, regular price, or sale price. Send only the fields to change. Prices are plain numbers in the store currency; send an empty sale_price to remove a sale.',
				schema: array(
					'type'                 => 'object',
					'properties'           => array(
						'id'                => array( 'type' => 'integer' ),
						'name'              => array( 'type' => 'string' ),
						'description'       => array( 'type' => 'string' ),
						'short_description' => array( 'type' => 'string' ),
						'regular_price'     => array( 'type' => 'string' ),
						'sale_price'        => array( 'type' => 'string' ),
					),
					'required'             => array( 'id' ),
					'additionalProperties' => false,
				),
				risk: Tool::RISK_WRITE,
				permission: static fn( array $args ): bool|\WP_Error => current_user_can( 'edit_product', (int) $args['id'] ) ? true : new \WP_Error( 'forbidden', __( 'You cannot edit this product.', 'mindio-magic-ai' ) ),
				handler: array( self::class, 'update' ),
				preview: array( self::class, 'preview_update' ),
				fingerprint: static fn( array $args ): string => md5( (string) wp_json_encode( ( new \MindioMagicAI\Agent\ChangeRecorder() )->product_snapshot( (int) $args['id'] ) ) ),
				reversible: true,
				group: 'woocommerce',
			)
		);

		$registry->register(
			new Tool(
				name: 'create_product_draft',
				label: __( 'Draft a product', 'mindio-magic-ai' ),
				description: 'Create a simple product as a draft (not visible in the store). Categories must already exist.',
				schema: array(
					'type'                 => 'object',
					'properties'           => array(
						'name'              => array( 'type' => 'string' ),
						'description'       => array( 'type' => 'string' ),
						'short_description' => array( 'type' => 'string' ),
						'regular_price'     => array( 'type' => 'string' ),
						'sku'               => array( 'type' => 'string' ),
						'categories'        => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
					'required'             => array( 'name' ),
					'additionalProperties' => false,
				),
				risk: Tool::RISK_WRITE,
				permission: $can_edit_products,
				handler: array( self::class, 'create' ),
				preview: array( self::class, 'preview_create' ),
				reversible: true,
				auto_apply: true,
				group: 'woocommerce',
			)
		);
	}

	private static function product( int $id ): \WC_Product|\WP_Error {
		$product = wc_get_product( $id );
		return $product instanceof \WC_Product ? $product : new \WP_Error( 'not_found', __( 'No product with that ID exists.', 'mindio-magic-ai' ) );
	}

	/** @return array<string,mixed> */
	private static function summary( \WC_Product $product ): array {
		return array(
			'id'             => $product->get_id(),
			'name'           => $product->get_name(),
			'type'           => $product->get_type(),
			'status'         => $product->get_status(),
			'sku'            => $product->get_sku(),
			'price'          => $product->get_price(),
			'regular_price'  => $product->get_regular_price(),
			'sale_price'     => $product->get_sale_price(),
			'stock_status'   => $product->get_stock_status(),
			'stock_quantity' => $product->managing_stock() ? $product->get_stock_quantity() : null,
			'link'           => (string) $product->get_permalink(),
			'edit_link'      => (string) get_edit_post_link( $product->get_id(), 'raw' ),
		);
	}

	/** @return array<string,mixed> */
	private static function target( \WC_Product $product ): array {
		return array(
			'type'      => 'product',
			'id'        => $product->get_id(),
			'title'     => $product->get_name(),
			'edit_link' => (string) get_edit_post_link( $product->get_id(), 'raw' ),
			'link'      => (string) $product->get_permalink(),
		);
	}

	/** @return array<string,mixed> */
	public static function search( array $args ): array {
		$query = array(
			'limit'   => Support::limit( $args['limit'] ?? 10, 10, 25 ),
			'status'  => 'any' === ( $args['status'] ?? 'any' ) ? array( 'publish', 'draft', 'pending', 'private' ) : (string) $args['status'],
			'orderby' => 'date',
			'order'   => 'DESC',
			'return'  => 'objects',
		);
		if ( ! empty( $args['query'] ) ) {
			$query['s'] = sanitize_text_field( (string) $args['query'] );
		}
		if ( ! empty( $args['category'] ) ) {
			$query['category'] = array( sanitize_title( (string) $args['category'] ) );
		}
		if ( ! empty( $args['stock_status'] ) ) {
			$query['stock_status'] = (string) $args['stock_status'];
		}
		$products = wc_get_products( $query );
		return array(
			'currency' => get_woocommerce_currency(),
			'items'    => array_map( array( self::class, 'summary' ), is_array( $products ) ? $products : array() ),
		);
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function get( array $args ): array|\WP_Error {
		$product = self::product( (int) $args['id'] );
		if ( is_wp_error( $product ) ) {
			return $product;
		}
		$attributes = array();
		foreach ( $product->get_attributes() as $attribute ) {
			if ( $attribute instanceof \WC_Product_Attribute ) {
				$attributes[] = array(
					'name'    => wc_attribute_label( $attribute->get_name() ),
					'options' => $attribute->is_taxonomy() ? wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) ) : $attribute->get_options(),
				);
			}
		}
		return self::summary( $product ) + array(
			'currency'          => get_woocommerce_currency(),
			'description'       => Support::clip( $product->get_description(), 12000 ),
			'short_description' => Support::clip( $product->get_short_description(), 4000 ),
			'categories'        => wc_get_product_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) ),
			'tags'              => wc_get_product_terms( $product->get_id(), 'product_tag', array( 'fields' => 'names' ) ),
			'attributes'        => $attributes,
			'average_rating'    => (float) $product->get_average_rating(),
			'review_count'      => (int) $product->get_review_count(),
			'total_sales'       => (int) $product->get_total_sales(),
			'images'            => count( array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) ) ),
		);
	}

	/** @return array<string,mixed> */
	public static function low_stock( array $args ): array {
		$threshold = isset( $args['threshold'] ) ? max( 0, (int) $args['threshold'] ) : (int) get_option( 'woocommerce_notify_low_stock_amount', 2 );
		$products  = wc_get_products(
			array(
				'limit'        => 500,
				'status'       => array( 'publish', 'private' ),
				'manage_stock' => true,
				'return'       => 'objects',
			)
		);
		$items = array();
		foreach ( is_array( $products ) ? $products : array() as $product ) {
			if ( $product instanceof \WC_Product && null !== $product->get_stock_quantity() && $product->get_stock_quantity() <= $threshold ) {
				$items[] = self::summary( $product );
			}
		}
		usort( $items, static fn( array $a, array $b ): int => (int) $a['stock_quantity'] <=> (int) $b['stock_quantity'] );
		$items = array_slice( $items, 0, Support::limit( $args['limit'] ?? 25, 25, 100 ) );
		return array(
			'threshold' => $threshold,
			'items'     => $items,
		);
	}

	/** @return array{0:string,1:string}|\WP_Error */
	private static function range( array $args ): array|\WP_Error {
		$from = (string) ( $args['from'] ?? '' );
		$to   = (string) ( $args['to'] ?? '' );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) || $from > $to ) {
			return new \WP_Error( 'invalid_range', __( 'Use dates in YYYY-MM-DD format, with from on or before to.', 'mindio-magic-ai' ) );
		}
		return array( $from . ' 00:00:00', $to . ' 23:59:59' );
	}

	private static function analytics_ready(): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'wc_order_stats';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Existence check.
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function sales_summary( array $args ): array|\WP_Error {
		$range = self::range( $args );
		if ( is_wp_error( $range ) ) {
			return $range;
		}
		if ( ! self::analytics_ready() ) {
			return new \WP_Error( 'analytics_unavailable', __( 'WooCommerce Analytics tables are not available. Enable WooCommerce Analytics and import historical data.', 'mindio-magic-ai' ) );
		}

		global $wpdb;
		$statuses     = self::PAID_STATUSES;
		$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Placeholder list is generated from a fixed array.
		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Fixed-shape report.
			$wpdb->prepare(
				"SELECT COUNT(*) AS orders, COALESCE(SUM(num_items_sold),0) AS items, COALESCE(SUM(total_sales),0) AS gross, COALESCE(SUM(net_total),0) AS net, COALESCE(SUM(shipping_total),0) AS shipping, COALESCE(SUM(tax_total),0) AS tax, COUNT(DISTINCT customer_id) AS customers
				FROM %i WHERE parent_id = 0 AND date_created BETWEEN %s AND %s AND status IN ($placeholders)",
				array_merge( array( $wpdb->prefix . 'wc_order_stats', $range[0], $range[1] ), $statuses )
			),
			ARRAY_A
		);
		$refunds = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Fixed-shape report.
			$wpdb->prepare(
				'SELECT COALESCE(SUM(net_total),0) FROM %i WHERE parent_id <> 0 AND date_created BETWEEN %s AND %s',
				$wpdb->prefix . 'wc_order_stats',
				$range[0],
				$range[1]
			)
		);
		// phpcs:enable

		$orders = (int) ( $row['orders'] ?? 0 );
		$net    = (float) ( $row['net'] ?? 0 );
		return array(
			'from'                => substr( $range[0], 0, 10 ),
			'to'                  => substr( $range[1], 0, 10 ),
			'currency'            => get_woocommerce_currency(),
			'orders'              => $orders,
			'items_sold'          => (int) ( $row['items'] ?? 0 ),
			'gross_sales'         => round( (float) ( $row['gross'] ?? 0 ), 2 ),
			'net_revenue'         => round( $net, 2 ),
			'shipping'            => round( (float) ( $row['shipping'] ?? 0 ), 2 ),
			'tax'                 => round( (float) ( $row['tax'] ?? 0 ), 2 ),
			'refunds'             => round( abs( (float) $refunds ), 2 ),
			'customers'           => (int) ( $row['customers'] ?? 0 ),
			'average_order_value' => $orders ? round( $net / $orders, 2 ) : 0,
			'counted_statuses'    => array( 'completed', 'processing', 'on-hold' ),
		);
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function top_products( array $args ): array|\WP_Error {
		$range = self::range( $args );
		if ( is_wp_error( $range ) ) {
			return $range;
		}
		if ( ! self::analytics_ready() ) {
			return new \WP_Error( 'analytics_unavailable', __( 'WooCommerce Analytics tables are not available. Enable WooCommerce Analytics and import historical data.', 'mindio-magic-ai' ) );
		}

		global $wpdb;
		$statuses     = self::PAID_STATUSES;
		$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Placeholder list is generated from a fixed array.
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Fixed-shape report.
			$wpdb->prepare(
				"SELECT l.product_id, SUM(l.product_qty) AS units, SUM(l.product_net_revenue) AS revenue
				FROM %i l INNER JOIN %i s ON s.order_id = l.order_id
				WHERE s.parent_id = 0 AND s.date_created BETWEEN %s AND %s AND s.status IN ($placeholders)
				GROUP BY l.product_id ORDER BY units DESC, revenue DESC LIMIT %d",
				array_merge( array( $wpdb->prefix . 'wc_order_product_lookup', $wpdb->prefix . 'wc_order_stats', $range[0], $range[1] ), $statuses, array( Support::limit( $args['limit'] ?? 10, 10, 50 ) ) )
			),
			ARRAY_A
		);
		// phpcs:enable

		$items = array();
		foreach ( (array) $rows as $row ) {
			$product = wc_get_product( (int) $row['product_id'] );
			$items[] = array(
				'product_id' => (int) $row['product_id'],
				'name'       => $product instanceof \WC_Product ? $product->get_name() : __( '(deleted product)', 'mindio-magic-ai' ),
				'units'      => (int) $row['units'],
				'revenue'    => round( (float) $row['revenue'], 2 ),
			);
		}
		return array(
			'from'     => substr( $range[0], 0, 10 ),
			'to'       => substr( $range[1], 0, 10 ),
			'currency' => get_woocommerce_currency(),
			'items'    => $items,
		);
	}

	/** @return array<string,string> */
	private static function fields( array $args ): array {
		$out = array();
		if ( isset( $args['name'] ) ) {
			$out['name'] = sanitize_text_field( (string) $args['name'] );
		}
		if ( isset( $args['description'] ) ) {
			$out['description'] = Support::clean_html( (string) $args['description'] );
		}
		if ( isset( $args['short_description'] ) ) {
			$out['short_description'] = Support::clean_html( (string) $args['short_description'] );
		}
		foreach ( array( 'regular_price', 'sale_price' ) as $price ) {
			if ( array_key_exists( $price, $args ) ) {
				$out[ $price ] = '' === trim( (string) $args[ $price ] ) ? '' : (string) wc_format_decimal( (string) $args[ $price ] );
			}
		}
		return $out;
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function preview_update( array $args ): array|\WP_Error {
		$product = self::product( (int) $args['id'] );
		if ( is_wp_error( $product ) ) {
			return $product;
		}
		$fields = self::fields( $args );
		if ( isset( $fields['sale_price'], $fields['regular_price'] ) && '' !== $fields['sale_price'] && (float) $fields['sale_price'] >= (float) $fields['regular_price'] ) {
			return new \WP_Error( 'invalid_price', __( 'The sale price must be lower than the regular price.', 'mindio-magic-ai' ) );
		}
		$before = array(
			'name'              => $product->get_name( 'edit' ),
			'description'       => $product->get_description( 'edit' ),
			'short_description' => $product->get_short_description( 'edit' ),
			'regular_price'     => (string) $product->get_regular_price( 'edit' ),
			'sale_price'        => (string) $product->get_sale_price( 'edit' ),
		);
		$changes = Support::diff(
			$before,
			$fields,
			array(
				'name'              => __( 'Name', 'mindio-magic-ai' ),
				'description'       => __( 'Description', 'mindio-magic-ai' ),
				'short_description' => __( 'Short description', 'mindio-magic-ai' ),
				'regular_price'     => __( 'Regular price', 'mindio-magic-ai' ),
				'sale_price'        => __( 'Sale price', 'mindio-magic-ai' ),
			)
		);
		if ( ! $changes ) {
			return new \WP_Error( 'nothing_to_change', __( 'The proposed values match the current product.', 'mindio-magic-ai' ) );
		}
		$preview = array(
			'summary' => sprintf(
				/* translators: %s: product name. */
				__( 'Edit product “%s”', 'mindio-magic-ai' ),
				$product->get_name()
			),
			'target'  => self::target( $product ),
			'changes' => $changes,
		);
		if ( isset( $fields['regular_price'] ) || isset( $fields['sale_price'] ) ) {
			$preview['warning'] = __( 'Price changes apply to the live store immediately.', 'mindio-magic-ai' );
		}
		return $preview;
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function update( array $args ): array|\WP_Error {
		$product = self::product( (int) $args['id'] );
		if ( is_wp_error( $product ) ) {
			return $product;
		}
		$fields = self::fields( $args );
		if ( isset( $fields['name'] ) ) {
			$product->set_name( $fields['name'] );
		}
		if ( isset( $fields['description'] ) ) {
			$product->set_description( $fields['description'] );
		}
		if ( isset( $fields['short_description'] ) ) {
			$product->set_short_description( $fields['short_description'] );
		}
		if ( array_key_exists( 'regular_price', $fields ) ) {
			$product->set_regular_price( $fields['regular_price'] );
		}
		if ( array_key_exists( 'sale_price', $fields ) ) {
			$product->set_sale_price( $fields['sale_price'] );
		}
		$product->save();
		return array(
			'product' => self::summary( $product ),
			'target'  => self::target( $product ),
		);
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function preview_create( array $args ): array|\WP_Error {
		$categories = Support::term_ids( (array) ( $args['categories'] ?? array() ), 'product_cat', false );
		if ( is_wp_error( $categories ) ) {
			return $categories;
		}
		if ( ! empty( $args['sku'] ) && wc_get_product_id_by_sku( (string) $args['sku'] ) ) {
			return new \WP_Error( 'duplicate_sku', __( 'Another product already uses that SKU.', 'mindio-magic-ai' ) );
		}
		$fields  = self::fields( $args );
		$changes = array();
		foreach ( array(
			'name'              => __( 'Name', 'mindio-magic-ai' ),
			'regular_price'     => __( 'Regular price', 'mindio-magic-ai' ),
			'short_description' => __( 'Short description', 'mindio-magic-ai' ),
			'description'       => __( 'Description', 'mindio-magic-ai' ),
		) as $field => $label ) {
			if ( isset( $fields[ $field ] ) && '' !== $fields[ $field ] ) {
				$changes[] = array(
					'field'  => $field,
					'label'  => $label,
					'before' => '',
					'after'  => Support::clip( $fields[ $field ], 4000 ),
				);
			}
		}
		return array(
			'summary' => sprintf(
				/* translators: %s: product name. */
				__( 'Create a draft product: “%s”', 'mindio-magic-ai' ),
				sanitize_text_field( (string) $args['name'] )
			),
			'changes' => $changes,
		);
	}

	/** @return array<string,mixed>|\WP_Error */
	public static function create( array $args ): array|\WP_Error {
		$categories = Support::term_ids( (array) ( $args['categories'] ?? array() ), 'product_cat', false );
		if ( is_wp_error( $categories ) ) {
			return $categories;
		}
		$fields  = self::fields( $args );
		$product = new \WC_Product_Simple();
		$product->set_status( 'draft' );
		$product->set_name( $fields['name'] ?? '' );
		$product->set_description( $fields['description'] ?? '' );
		$product->set_short_description( $fields['short_description'] ?? '' );
		if ( isset( $fields['regular_price'] ) ) {
			$product->set_regular_price( $fields['regular_price'] );
		}
		if ( $categories ) {
			$product->set_category_ids( $categories );
		}
		try {
			if ( ! empty( $args['sku'] ) ) {
				$product->set_sku( sanitize_text_field( (string) $args['sku'] ) );
			}
			$product->save();
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'product_failed', wp_strip_all_tags( $e->getMessage() ) );
		}
		return array(
			'product' => self::summary( $product ),
			'target'  => self::target( $product ),
		);
	}
}
