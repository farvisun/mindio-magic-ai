<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions;

enum BetaManagedAgentsDeltaType: string
{
    case AGENT_MESSAGE = 'agent.message';

    case AGENT_THINKING = 'agent.thinking';
}
