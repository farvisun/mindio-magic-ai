/**
 * Labels for assistant tools as shown in the activity trail.
 */
import { __ } from '@wordpress/i18n';

export function toolLabel( name, done = false ) {
	const labels = {
		site_overview: [
			__( 'Reading the site overview…', 'mindio-magic-ai' ),
			__( 'Read the site overview', 'mindio-magic-ai' ),
		],
		search_content: [
			__( 'Searching content…', 'mindio-magic-ai' ),
			__( 'Searched content', 'mindio-magic-ai' ),
		],
		get_content: [
			__( 'Reading content…', 'mindio-magic-ai' ),
			__( 'Read content', 'mindio-magic-ai' ),
		],
		list_terms: [
			__( 'Listing categories and tags…', 'mindio-magic-ai' ),
			__( 'Listed categories and tags', 'mindio-magic-ai' ),
		],
		create_draft: [
			__( 'Preparing a draft…', 'mindio-magic-ai' ),
			__( 'Prepared a draft', 'mindio-magic-ai' ),
		],
		update_content: [
			__( 'Preparing an edit…', 'mindio-magic-ai' ),
			__( 'Prepared an edit', 'mindio-magic-ai' ),
		],
		schedule_content: [
			__( 'Preparing a schedule…', 'mindio-magic-ai' ),
			__( 'Prepared a schedule', 'mindio-magic-ai' ),
		],
		publish_content: [
			__( 'Preparing to publish…', 'mindio-magic-ai' ),
			__( 'Prepared to publish', 'mindio-magic-ai' ),
		],
		set_terms: [
			__( 'Preparing categories and tags…', 'mindio-magic-ai' ),
			__( 'Prepared categories and tags', 'mindio-magic-ai' ),
		],
		list_comments: [
			__( 'Reading comments…', 'mindio-magic-ai' ),
			__( 'Read comments', 'mindio-magic-ai' ),
		],
		reply_to_comment: [
			__( 'Preparing a reply…', 'mindio-magic-ai' ),
			__( 'Prepared a reply', 'mindio-magic-ai' ),
		],
		moderate_comment: [
			__( 'Preparing moderation…', 'mindio-magic-ai' ),
			__( 'Prepared moderation', 'mindio-magic-ai' ),
		],
		get_seo_meta: [
			__( 'Reading SEO fields…', 'mindio-magic-ai' ),
			__( 'Read SEO fields', 'mindio-magic-ai' ),
		],
		update_seo_meta: [
			__( 'Preparing SEO changes…', 'mindio-magic-ai' ),
			__( 'Prepared SEO changes', 'mindio-magic-ai' ),
		],
		search_products: [
			__( 'Searching products…', 'mindio-magic-ai' ),
			__( 'Searched products', 'mindio-magic-ai' ),
		],
		get_product: [
			__( 'Reading a product…', 'mindio-magic-ai' ),
			__( 'Read a product', 'mindio-magic-ai' ),
		],
		low_stock_products: [
			__( 'Checking stock…', 'mindio-magic-ai' ),
			__( 'Checked stock', 'mindio-magic-ai' ),
		],
		sales_summary: [
			__( 'Summarizing sales…', 'mindio-magic-ai' ),
			__( 'Summarized sales', 'mindio-magic-ai' ),
		],
		top_products: [
			__( 'Ranking products…', 'mindio-magic-ai' ),
			__( 'Ranked products', 'mindio-magic-ai' ),
		],
		update_product: [
			__( 'Preparing a product edit…', 'mindio-magic-ai' ),
			__( 'Prepared a product edit', 'mindio-magic-ai' ),
		],
		create_product_draft: [
			__( 'Preparing a product draft…', 'mindio-magic-ai' ),
			__( 'Prepared a product draft', 'mindio-magic-ai' ),
		],
	};
	const pair = labels[ name ];
	if ( ! pair ) {
		return done
			? __( 'Used a tool', 'mindio-magic-ai' )
			: __( 'Working…', 'mindio-magic-ai' );
	}
	return done ? pair[ 1 ] : pair[ 0 ];
}

export function suggestions( woocommerce ) {
	const list = [
		{
			icon: 'file-text',
			text: __(
				'Find drafts I haven’t touched in a month and summarize each one',
				'mindio-magic-ai'
			),
		},
		{
			icon: 'wand-sparkles',
			text: __(
				'Write a blog post outline about our most popular topic',
				'mindio-magic-ai'
			),
		},
		{
			icon: 'message-square',
			text: __(
				'Review comments waiting for moderation',
				'mindio-magic-ai'
			),
		},
		{
			icon: 'search',
			text: __(
				'Which published posts have no excerpt?',
				'mindio-magic-ai'
			),
		},
	];
	if ( woocommerce ) {
		list.splice( 1, 0, {
			icon: 'chart-column',
			text: __(
				'Summarize this month’s sales and top products',
				'mindio-magic-ai'
			),
		} );
		list.push( {
			icon: 'shopping-cart',
			text: __(
				'List products that are low in stock',
				'mindio-magic-ai'
			),
		} );
	}
	return list.slice( 0, 5 );
}
