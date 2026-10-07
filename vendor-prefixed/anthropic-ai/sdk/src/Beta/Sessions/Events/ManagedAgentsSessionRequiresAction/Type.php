<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionRequiresAction;

enum Type: string
{
    case REQUIRES_ACTION = 'requires_action';
}
