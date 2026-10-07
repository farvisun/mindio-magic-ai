<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsModelRateLimitedError;

enum Type: string
{
    case MODEL_RATE_LIMITED_ERROR = 'model_rate_limited_error';
}
