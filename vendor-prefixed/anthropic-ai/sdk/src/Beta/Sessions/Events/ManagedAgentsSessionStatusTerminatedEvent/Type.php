<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionStatusTerminatedEvent;

enum Type: string
{
    case SESSION_STATUS_TERMINATED = 'session.status_terminated';
}
