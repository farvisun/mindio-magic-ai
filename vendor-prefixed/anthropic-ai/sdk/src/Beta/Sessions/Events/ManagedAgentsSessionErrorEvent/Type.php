<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionErrorEvent;

enum Type: string
{
    case SESSION_ERROR = 'session.error';
}
