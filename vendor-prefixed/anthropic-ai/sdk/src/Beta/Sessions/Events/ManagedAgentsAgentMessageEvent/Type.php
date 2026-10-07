<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsAgentMessageEvent;

enum Type: string
{
    case AGENT_MESSAGE = 'agent.message';
}
