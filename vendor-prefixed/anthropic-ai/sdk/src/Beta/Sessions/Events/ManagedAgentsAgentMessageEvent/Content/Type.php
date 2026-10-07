<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsAgentMessageEvent\Content;

enum Type: string
{
    case TEXT = 'text';

    case REDACTED = 'redacted';
}
