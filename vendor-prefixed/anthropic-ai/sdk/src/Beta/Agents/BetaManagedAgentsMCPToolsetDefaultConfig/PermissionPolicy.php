<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsMCPToolsetDefaultConfig;

use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsAlwaysAllowPolicy;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsAlwaysAskPolicy;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsAutoPolicy;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * Permission policy for tool execution.
 *
 * @phpstan-import-type BetaManagedAgentsAlwaysAllowPolicyShape from \Anthropic\Beta\Agents\BetaManagedAgentsAlwaysAllowPolicy
 * @phpstan-import-type BetaManagedAgentsAlwaysAskPolicyShape from \Anthropic\Beta\Agents\BetaManagedAgentsAlwaysAskPolicy
 * @phpstan-import-type BetaManagedAgentsAutoPolicyShape from \Anthropic\Beta\Agents\BetaManagedAgentsAutoPolicy
 *
 * @phpstan-type PermissionPolicyVariants = BetaManagedAgentsAlwaysAllowPolicy|BetaManagedAgentsAlwaysAskPolicy|BetaManagedAgentsAutoPolicy
 * @phpstan-type PermissionPolicyShape = PermissionPolicyVariants|BetaManagedAgentsAlwaysAllowPolicyShape|BetaManagedAgentsAlwaysAskPolicyShape|BetaManagedAgentsAutoPolicyShape
 */
final class PermissionPolicy implements ConverterSource
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
            'always_allow' => BetaManagedAgentsAlwaysAllowPolicy::class,
            'always_ask' => BetaManagedAgentsAlwaysAskPolicy::class,
            'auto' => BetaManagedAgentsAutoPolicy::class,
        ];
    }
}
