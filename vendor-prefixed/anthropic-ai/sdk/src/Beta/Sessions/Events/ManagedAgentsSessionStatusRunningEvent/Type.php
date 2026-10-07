<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionStatusRunningEvent;

enum Type: string
{
    case SESSION_STATUS_RUNNING = 'session.status_running';
}
