<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSendSessionEvents;

use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsSystemMessageEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsUserToolResultEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsUserCustomToolResultEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsUserDefineOutcomeEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsUserInterruptEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsUserMessageEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsUserToolConfirmationEvent;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * Union type for events that can be sent to a session.
 *
 * @phpstan-import-type ManagedAgentsUserMessageEventShape from \Anthropic\Beta\Sessions\Events\ManagedAgentsUserMessageEvent
 * @phpstan-import-type ManagedAgentsUserInterruptEventShape from \Anthropic\Beta\Sessions\Events\ManagedAgentsUserInterruptEvent
 * @phpstan-import-type ManagedAgentsUserToolConfirmationEventShape from \Anthropic\Beta\Sessions\Events\ManagedAgentsUserToolConfirmationEvent
 * @phpstan-import-type ManagedAgentsUserCustomToolResultEventShape from \Anthropic\Beta\Sessions\Events\ManagedAgentsUserCustomToolResultEvent
 * @phpstan-import-type ManagedAgentsUserDefineOutcomeEventShape from \Anthropic\Beta\Sessions\Events\ManagedAgentsUserDefineOutcomeEvent
 * @phpstan-import-type BetaManagedAgentsUserToolResultEventShape from \Anthropic\Beta\Sessions\BetaManagedAgentsUserToolResultEvent
 * @phpstan-import-type BetaManagedAgentsSystemMessageEventShape from \Anthropic\Beta\Sessions\BetaManagedAgentsSystemMessageEvent
 *
 * @phpstan-type DataVariants = ManagedAgentsUserMessageEvent|ManagedAgentsUserInterruptEvent|ManagedAgentsUserToolConfirmationEvent|ManagedAgentsUserCustomToolResultEvent|ManagedAgentsUserDefineOutcomeEvent|BetaManagedAgentsUserToolResultEvent|BetaManagedAgentsSystemMessageEvent
 * @phpstan-type DataShape = DataVariants|ManagedAgentsUserMessageEventShape|ManagedAgentsUserInterruptEventShape|ManagedAgentsUserToolConfirmationEventShape|ManagedAgentsUserCustomToolResultEventShape|ManagedAgentsUserDefineOutcomeEventShape|BetaManagedAgentsUserToolResultEventShape|BetaManagedAgentsSystemMessageEventShape
 */
final class Data implements ConverterSource
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
            'user.message' => ManagedAgentsUserMessageEvent::class,
            'user.interrupt' => ManagedAgentsUserInterruptEvent::class,
            'user.tool_confirmation' => ManagedAgentsUserToolConfirmationEvent::class,
            'user.custom_tool_result' => ManagedAgentsUserCustomToolResultEvent::class,
            'user.define_outcome' => ManagedAgentsUserDefineOutcomeEvent::class,
            'user.tool_result' => BetaManagedAgentsUserToolResultEvent::class,
            'system.message' => BetaManagedAgentsSystemMessageEvent::class,
        ];
    }
}
