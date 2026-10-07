<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsUserInterruptEvent;

enum Type: string
{
    case USER_INTERRUPT = 'user.interrupt';
}
