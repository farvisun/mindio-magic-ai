<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSpanModelRequestStartEvent;

enum Type: string
{
    case SPAN_MODEL_REQUEST_START = 'span.model_request_start';
}
