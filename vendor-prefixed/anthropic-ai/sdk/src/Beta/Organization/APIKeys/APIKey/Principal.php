<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\APIKeys\APIKey;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\APIKeys\APIKeyServiceAccountActor;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\APIKeys\APIKeyUserActor;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * The principal the API key acts as (a User or a Service Account), or `null` if the API key is not bound to a principal.
 *
 * @phpstan-import-type APIKeyUserActorShape from \Anthropic\Beta\Organization\APIKeys\APIKeyUserActor
 * @phpstan-import-type APIKeyServiceAccountActorShape from \Anthropic\Beta\Organization\APIKeys\APIKeyServiceAccountActor
 *
 * @phpstan-type PrincipalVariants = APIKeyUserActor|APIKeyServiceAccountActor
 * @phpstan-type PrincipalShape = PrincipalVariants|APIKeyUserActorShape|APIKeyServiceAccountActorShape
 */
final class Principal implements ConverterSource
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
            'user_actor' => APIKeyUserActor::class,
            'service_account_actor' => APIKeyServiceAccountActor::class,
        ];
    }
}
