<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Deployments;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * An event sent to a session immediately after it is created. Supports `user.message`, `user.define_outcome`, and `system.message`.
 *
 * @phpstan-import-type BetaManagedAgentsDeploymentUserMessageEventShape from \Anthropic\Beta\Deployments\BetaManagedAgentsDeploymentUserMessageEvent
 * @phpstan-import-type BetaManagedAgentsDeploymentUserDefineOutcomeEventShape from \Anthropic\Beta\Deployments\BetaManagedAgentsDeploymentUserDefineOutcomeEvent
 * @phpstan-import-type BetaManagedAgentsDeploymentSystemMessageEventShape from \Anthropic\Beta\Deployments\BetaManagedAgentsDeploymentSystemMessageEvent
 *
 * @phpstan-type BetaManagedAgentsDeploymentInitialEventVariants = BetaManagedAgentsDeploymentUserMessageEvent|BetaManagedAgentsDeploymentUserDefineOutcomeEvent|BetaManagedAgentsDeploymentSystemMessageEvent
 * @phpstan-type BetaManagedAgentsDeploymentInitialEventShape = BetaManagedAgentsDeploymentInitialEventVariants|BetaManagedAgentsDeploymentUserMessageEventShape|BetaManagedAgentsDeploymentUserDefineOutcomeEventShape|BetaManagedAgentsDeploymentSystemMessageEventShape
 */
final class BetaManagedAgentsDeploymentInitialEvent implements ConverterSource
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
            'user.message' => BetaManagedAgentsDeploymentUserMessageEvent::class,
            'user.define_outcome' => BetaManagedAgentsDeploymentUserDefineOutcomeEvent::class,
            'system.message' => BetaManagedAgentsDeploymentSystemMessageEvent::class,
        ];
    }
}
