<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\SessionCreateParams;

use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsAgentParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsAgentWithOverridesParams;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * Agent identifier. Accepts the `agent` ID string, which pins the latest version for the session, or an `agent` object with both id and version specified.
 *
 * @phpstan-import-type BetaManagedAgentsAgentParamsShape from \Anthropic\Beta\Sessions\BetaManagedAgentsAgentParams
 * @phpstan-import-type BetaManagedAgentsAgentWithOverridesParamsShape from \Anthropic\Beta\Sessions\BetaManagedAgentsAgentWithOverridesParams
 *
 * @phpstan-type AgentVariants = string|BetaManagedAgentsAgentParams|BetaManagedAgentsAgentWithOverridesParams
 * @phpstan-type AgentShape = AgentVariants|BetaManagedAgentsAgentParamsShape|BetaManagedAgentsAgentWithOverridesParamsShape
 */
final class Agent implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [
            BetaManagedAgentsAgentParams::class,
            BetaManagedAgentsAgentWithOverridesParams::class,
            'string',
        ];
    }
}
