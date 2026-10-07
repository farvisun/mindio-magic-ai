<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionThreadStatusIdleEvent;

enum Type: string
{
    case SESSION_THREAD_STATUS_IDLE = 'session.thread_status_idle';
}
