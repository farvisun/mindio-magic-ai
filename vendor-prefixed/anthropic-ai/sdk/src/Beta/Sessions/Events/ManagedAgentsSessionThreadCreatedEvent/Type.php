<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionThreadCreatedEvent;

enum Type: string
{
    case SESSION_THREAD_CREATED = 'session.thread_created';
}
