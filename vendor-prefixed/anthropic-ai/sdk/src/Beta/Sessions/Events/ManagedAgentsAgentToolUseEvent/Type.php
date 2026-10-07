<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsAgentToolUseEvent;

enum Type: string
{
    case AGENT_TOOL_USE = 'agent.tool_use';
}
