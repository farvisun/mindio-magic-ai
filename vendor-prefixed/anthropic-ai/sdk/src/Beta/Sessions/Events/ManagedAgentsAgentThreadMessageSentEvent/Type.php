<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsAgentThreadMessageSentEvent;

enum Type: string
{
    case AGENT_THREAD_MESSAGE_SENT = 'agent.thread_message_sent';
}
