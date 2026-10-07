<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Threads\ManagedAgentsSessionThread;

use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsAdvisor;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsSessionThreadAgent;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * Resolved agent definition for this thread. Snapshot of the agent at thread creation time.
 *
 * @phpstan-import-type BetaManagedAgentsSessionThreadAgentShape from \Anthropic\Beta\Agents\BetaManagedAgentsSessionThreadAgent
 * @phpstan-import-type BetaManagedAgentsAdvisorShape from \Anthropic\Beta\Agents\BetaManagedAgentsAdvisor
 *
 * @phpstan-type AgentVariants = BetaManagedAgentsSessionThreadAgent|BetaManagedAgentsAdvisor
 * @phpstan-type AgentShape = AgentVariants|BetaManagedAgentsSessionThreadAgentShape|BetaManagedAgentsAdvisorShape
 */
final class Agent implements ConverterSource
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
            'agent' => BetaManagedAgentsSessionThreadAgent::class,
            'advisor' => BetaManagedAgentsAdvisor::class,
        ];
    }
}
