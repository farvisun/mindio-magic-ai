<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Vaults\Credentials\ManagedAgentsMCPOAuthRefreshResponse;

use MindioMagicAI\Vendor\Anthropic\Beta\Vaults\Credentials\ManagedAgentsTokenEndpointAuthBasicResponse;
use MindioMagicAI\Vendor\Anthropic\Beta\Vaults\Credentials\ManagedAgentsTokenEndpointAuthNoneResponse;
use MindioMagicAI\Vendor\Anthropic\Beta\Vaults\Credentials\ManagedAgentsTokenEndpointAuthPostResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type ManagedAgentsTokenEndpointAuthNoneResponseShape from \Anthropic\Beta\Vaults\Credentials\ManagedAgentsTokenEndpointAuthNoneResponse
 * @phpstan-import-type ManagedAgentsTokenEndpointAuthBasicResponseShape from \Anthropic\Beta\Vaults\Credentials\ManagedAgentsTokenEndpointAuthBasicResponse
 * @phpstan-import-type ManagedAgentsTokenEndpointAuthPostResponseShape from \Anthropic\Beta\Vaults\Credentials\ManagedAgentsTokenEndpointAuthPostResponse
 *
 * @phpstan-type TokenEndpointAuthVariants = ManagedAgentsTokenEndpointAuthNoneResponse|ManagedAgentsTokenEndpointAuthBasicResponse|ManagedAgentsTokenEndpointAuthPostResponse
 * @phpstan-type TokenEndpointAuthShape = TokenEndpointAuthVariants|ManagedAgentsTokenEndpointAuthNoneResponseShape|ManagedAgentsTokenEndpointAuthBasicResponseShape|ManagedAgentsTokenEndpointAuthPostResponseShape
 */
final class TokenEndpointAuth implements ConverterSource
{
    use SdkUnion;

    public static function discriminator(): string
    {
        return 'type';
    }

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [
            'none' => ManagedAgentsTokenEndpointAuthNoneResponse::class,
            'client_secret_basic' => ManagedAgentsTokenEndpointAuthBasicResponse::class,
            'client_secret_post' => ManagedAgentsTokenEndpointAuthPostResponse::class,
        ];
    }
}
