<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\SpendLimits\IncreaseRequests\IncreaseRequestApproveResponse;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\SpendLimits\SpendLimitScopedAPIKeyActor;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\SpendLimits\SpendLimitUserActor;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type SpendLimitUserActorShape from \Anthropic\Beta\Organization\SpendLimits\SpendLimitUserActor
 * @phpstan-import-type SpendLimitScopedAPIKeyActorShape from \Anthropic\Beta\Organization\SpendLimits\SpendLimitScopedAPIKeyActor
 *
 * @phpstan-type ResolvedByVariants = SpendLimitUserActor|SpendLimitScopedAPIKeyActor
 * @phpstan-type ResolvedByShape = ResolvedByVariants|SpendLimitUserActorShape|SpendLimitScopedAPIKeyActorShape
 */
final class ResolvedBy implements ConverterSource
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
            'user_actor' => SpendLimitUserActor::class,
            'scoped_api_key_actor' => SpendLimitScopedAPIKeyActor::class,
        ];
    }
}
