<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionDeletedEvent;

enum Type: string
{
    case SESSION_DELETED = 'session.deleted';
}
