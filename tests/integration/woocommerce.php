<?php
/**
 * WooCommerce tools: product reads, price edits with undo through WooCommerce CRUD, and reports.
 *
 * @package MindioMagicAI
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use MindioMagicAI\Agent\ProposalQueue;
use MindioMagicAI\Agent\ToolRegistry;

Mindio_Test::run(
	'woocommerce',
	static function (): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo "SKIP woocommerce: WooCommerce is not active\n";
			return;
		}
		$admin = Mindio_Test::user( 'administrator' );
		Mindio_Test::as( $admin );

		$product = new WC_Product_Simple();
		$product->set_name( 'Mindio test mug' );
		$product->set_regular_price( '120000' );
		$product->set_status( 'publish' );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1 );
		$product->set_sku( 'MINDIO-MUG-' . wp_generate_password( 4, false ) );
		$product->save();
		$id = $product->get_id();
		Mindio_Test::track_post( $id );

		$registry = ToolRegistry::instance();
		foreach ( array( 'search_products', 'get_product', 'low_stock_products', 'sales_summary', 'top_products', 'update_product', 'create_product_draft' ) as $name ) {
			Mindio_Test::assert( null !== $registry->get( $name ), 'tool registered: ' . $name );
		}

		$found = $registry->get( 'search_products' )->run( array( 'query' => 'Mindio test mug' ) );
		Mindio_Test::same( $id, $found['items'][0]['id'] ?? 0, 'product search finds the product' );
		$low = $registry->get( 'low_stock_products' )->run( array( 'threshold' => 2 ) );
		Mindio_Test::assert( in_array( $id, array_column( $low['items'], 'id' ), true ), 'low stock includes the product' );
		$sales = $registry->get( 'sales_summary' )->run( array( 'from' => wp_date( 'Y-m-01' ), 'to' => wp_date( 'Y-m-d' ) ) );
		Mindio_Test::assert( is_wp_error( $sales ) || isset( $sales['orders'], $sales['net_revenue'] ), 'sales summary has a fixed shape' );
		$invalid = $registry->get( 'sales_summary' )->run( array( 'from' => '2026-02-01; DROP TABLE x', 'to' => '2026-01-01' ) );
		Mindio_Test::assert( is_wp_error( $invalid ), 'invalid dates are refused' );

		// Price edit through a proposal, then undo through WooCommerce CRUD.
		$tool    = $registry->get( 'update_product' );
		$args    = array( 'id' => $id, 'sale_price' => '99000', 'description' => '<p>A sturdy mug.</p>' );
		$preview = $tool->preview( $args );
		Mindio_Test::assert( ! empty( $preview['warning'] ), 'price changes carry a warning' );
		$pid     = ProposalQueue::create( 0, 0, $admin, $tool, $args, $preview );
		$applied = ProposalQueue::apply( $pid, $admin );
		Mindio_Test::assert( ! is_wp_error( $applied ) && $applied['can_undo'], 'product edit applies and is undoable' );
		$fresh = wc_get_product( $id );
		Mindio_Test::same( '99000', $fresh->get_sale_price(), 'sale price set' );
		Mindio_Test::same( '99000', $fresh->get_price(), 'active price follows the sale price' );

		$undo = ProposalQueue::undo( $pid, $admin, false );
		Mindio_Test::assert( ! is_wp_error( $undo ), 'undo succeeds' );
		wc_delete_product_transients( $id );
		$restored = wc_get_product( $id );
		Mindio_Test::same( '', $restored->get_sale_price(), 'sale price removed' );
		Mindio_Test::same( '120000', $restored->get_price(), 'active price restored' );
		Mindio_Test::same( '', $restored->get_description(), 'description restored' );

		global $wpdb;
		$lookup = $wpdb->get_var( $wpdb->prepare( 'SELECT min_price FROM %i WHERE product_id = %d', $wpdb->prefix . 'wc_product_meta_lookup', $id ) );
		Mindio_Test::same( 120000.0, (float) $lookup, 'lookup table matches the restored price' );

		$bad = $tool->preview( array( 'id' => $id, 'regular_price' => '100', 'sale_price' => '200' ) );
		Mindio_Test::assert( is_wp_error( $bad ), 'sale price above regular price is refused' );

		// Shop managers need edit_products for product tools.
		$editor = Mindio_Test::user( 'editor' );
		Mindio_Test::as( $editor );
		Mindio_Test::assert( true !== $tool->permitted( array( 'id' => $id ) ), 'editors cannot edit products' );
	}
);
