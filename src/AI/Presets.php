<?php
/**
 * Known providers and gateways. Every preset is optional and nothing is contacted until an administrator saves a key.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Presets {
	public const PROVIDER_ANTHROPIC = 'anthropic';
	public const PROVIDER_OPENAI    = 'openai_compatible';

	/**
	 * @return array<string,array{label:string,provider:string,base_url:string,editable_url:bool,constant:string,default_model:string,fast_model:string,suggested:array<int,string>,keys_url:string,max_tokens_param:string}>
	 */
	public static function all(): array {
		/**
		 * Filter the provider presets offered when adding a connection.
		 *
		 * @param array<string,array<string,mixed>> $presets
		 */
		return (array) apply_filters( 'mindio_magic_ai_presets', self::defaults() );
	}

	/** @return array<string,array<string,mixed>> */
	private static function defaults(): array {
		return array(
			'anthropic' => array(
				'label'            => 'Anthropic Claude',
				'provider'         => self::PROVIDER_ANTHROPIC,
				'base_url'         => 'https://api.anthropic.com',
				'editable_url'     => false,
				'constant'         => 'MINDIO_MAGIC_AI_ANTHROPIC_KEY',
				'default_model'    => 'claude-opus-5-5',
				'fast_model'       => 'claude-haiku-5-5',
				'suggested'        => array( 'claude-opus-5-5', 'claude-sonnet-5-5', 'claude-haiku-5-5' ),
				'keys_url'         => 'https://console.anthropic.com/settings/keys',
				'max_tokens_param' => 'max_tokens',
			),
			'openai'    => array(
				'label'            => 'OpenAI',
				'provider'         => self::PROVIDER_OPENAI,
				'base_url'         => 'https://api.openai.com/v1',
				'editable_url'     => false,
				'constant'         => 'MINDIO_MAGIC_AI_OPENAI_KEY',
				'default_model'    => '',
				'fast_model'       => '',
				'suggested'        => array(),
				'keys_url'         => 'https://platform.openai.com/api-keys',
				'max_tokens_param' => 'max_completion_tokens',
			),
			'gemini'    => array(
				'label'            => 'Google Gemini',
				'provider'         => self::PROVIDER_OPENAI,
				'base_url'         => 'https://generativelanguage.googleapis.com/v1beta/openai',
				'editable_url'     => false,
				'constant'         => 'MINDIO_MAGIC_AI_GEMINI_KEY',
				'default_model'    => '',
				'fast_model'       => '',
				'suggested'        => array(),
				'keys_url'         => 'https://aistudio.google.com/app/apikey',
				'max_tokens_param' => 'max_tokens',
			),
			'deepseek'  => array(
				'label'            => 'DeepSeek',
				'provider'         => self::PROVIDER_OPENAI,
				'base_url'         => 'https://api.deepseek.com/v1',
				'editable_url'     => false,
				'constant'         => 'MINDIO_MAGIC_AI_DEEPSEEK_KEY',
				'default_model'    => 'deepseek-chat',
				'fast_model'       => 'deepseek-chat',
				'suggested'        => array( 'deepseek-chat' ),
				'keys_url'         => 'https://platform.deepseek.com/api_keys',
				'max_tokens_param' => 'max_tokens',
			),
			'custom'    => array(
				'label'            => __( 'OpenAI-compatible (custom URL)', 'mindio-magic-ai' ),
				'provider'         => self::PROVIDER_OPENAI,
				'base_url'         => '',
				'editable_url'     => true,
				'constant'         => '',
				'default_model'    => '',
				'fast_model'       => '',
				'suggested'        => array(),
				'keys_url'         => '',
				'max_tokens_param' => 'max_tokens',
			),
		);
	}

	/** @return array<string,mixed>|null */
	public static function get( string $preset ): ?array {
		return self::all()[ $preset ] ?? null;
	}
}
