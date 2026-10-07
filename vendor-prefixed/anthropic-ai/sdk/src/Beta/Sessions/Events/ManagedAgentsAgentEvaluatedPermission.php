<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events;

enum ManagedAgentsAgentEvaluatedPermission: string
{
    case ALLOW = 'allow';

    case ASK = 'ask';

    case DENY = 'deny';
}
