<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsModelOverloadedError;

enum Type: string
{
    case MODEL_OVERLOADED_ERROR = 'model_overloaded_error';
}
