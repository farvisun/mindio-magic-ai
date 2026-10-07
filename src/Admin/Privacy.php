<?php
/**
 * Privacy policy text and the personal data exporter and eraser for assistant conversations.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\Admin;

use MindioMagicAI\Assistant\Conversations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Privacy {
	public static function register(): void {
		add_action( 'admin_init', array( self::class, 'policy' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( self::class, 'exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( self::class, 'erasers' ) );
	}

	public static function policy(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		$content = '<p>' . esc_html__( 'This site uses Mindio Magic AI to help staff manage the site. When a staff member uses the assistant, their messages, any images they attach, and the site content the assistant reads to answer them are sent to the AI provider the site administrator configured (for example Anthropic, OpenAI, Google, or another compatible provider). The provider processes this data under its own terms and privacy policy.', 'mindio-magic-ai' ) . '</p>'
			. '<p>' . esc_html__( 'Assistant conversations are stored in this site’s database for the retention period set by the administrator and can be exported or erased on request.', 'mindio-magic-ai' ) . '</p>';
		wp_add_privacy_policy_content( 'Mindio Magic AI', wp_kses_post( $content ) );
	}

	/**
	 * @param array<string,mixed> $exporters
	 * @return array<string,mixed>
	 */
	public static function exporters( array $exporters ): array {
		$exporters['mindio-magic-ai'] = array(
			'exporter_friendly_name' => __( 'Mindio AI conversations', 'mindio-magic-ai' ),
			'callback'               => array( self::class, 'export' ),
		);
		return $exporters;
	}

	/**
	 * @param array<string,mixed> $erasers
	 * @return array<string,mixed>
	 */
	public static function erasers( array $erasers ): array {
		$erasers['mindio-magic-ai'] = array(
			'eraser_friendly_name' => __( 'Mindio AI conversations', 'mindio-magic-ai' ),
			'callback'             => array( self::class, 'erase' ),
		);
		return $erasers;
	}

	/** @return array{data:array<int,array<string,mixed>>,done:bool} */
	public static function export( string $email, int $page = 1 ): array {
		$user = get_user_by( 'email', $email );
		if ( ! $user instanceof \WP_User ) {
			return array( 'data' => array(), 'done' => true );
		}
		$conversations = new Conversations();
		$data          = array();
		foreach ( $conversations->list( $user->ID, '', false, 200 ) as $conversation ) {
			$text = array();
			foreach ( $conversations->messages( (int) $conversation['id'] ) as $message ) {
				if ( 'user' !== $message['role'] && 'assistant' !== $message['role'] ) {
					continue;
				}
				foreach ( $message['content'] as $block ) {
					if ( 'text' === ( $block['type'] ?? '' ) ) {
						$text[] = ( 'user' === $message['role'] ? '> ' : '' ) . (string) $block['text'];
					}
				}
			}
			$data[] = array(
				'group_id'    => 'mindio-ai-conversations',
				'group_label' => __( 'Mindio AI conversations', 'mindio-magic-ai' ),
				'item_id'     => 'mindio-ai-conversation-' . (int) $conversation['id'],
				'data'        => array(
					array(
						'name'  => __( 'Title', 'mindio-magic-ai' ),
						'value' => (string) $conversation['title'],
					),
					array(
						'name'  => __( 'Started', 'mindio-magic-ai' ),
						'value' => (string) $conversation['created_at'],
					),
					array(
						'name'  => __( 'Messages', 'mindio-magic-ai' ),
						'value' => implode( "\n\n", $text ),
					),
				),
			);
		}
		unset( $page );
		return array( 'data' => $data, 'done' => true );
	}

	/** @return array{items_removed:bool,items_retained:bool,messages:array<int,string>,done:bool} */
	public static function erase( string $email, int $page = 1 ): array {
		unset( $page );
		$user    = get_user_by( 'email', $email );
		$removed = false;
		if ( $user instanceof \WP_User ) {
			$conversations = new Conversations();
			foreach ( $conversations->list( $user->ID, '', false, 200 ) as $conversation ) {
				$conversations->delete( (int) $conversation['id'] );
				$removed = true;
			}
			foreach ( $conversations->list( $user->ID, '', true, 200 ) as $conversation ) {
				$conversations->delete( (int) $conversation['id'] );
				$removed = true;
			}
		}
		return array(
			'items_removed'  => $removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}
