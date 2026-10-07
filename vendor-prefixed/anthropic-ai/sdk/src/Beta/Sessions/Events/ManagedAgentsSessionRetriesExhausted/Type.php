<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionRetriesExhausted;

enum Type: string
{
    case RETRIES_EXHAUSTED = 'retries_exhausted';
}
