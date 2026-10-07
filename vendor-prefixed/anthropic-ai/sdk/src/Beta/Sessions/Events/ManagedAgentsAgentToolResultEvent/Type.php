<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsAgentToolResultEvent;

enum Type: string
{
    case AGENT_TOOL_RESULT = 'agent.tool_result';
}
