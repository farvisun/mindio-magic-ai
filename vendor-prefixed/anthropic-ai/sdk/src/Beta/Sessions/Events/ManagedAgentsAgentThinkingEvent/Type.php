<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsAgentThinkingEvent;

enum Type: string
{
    case AGENT_THINKING = 'agent.thinking';
}
