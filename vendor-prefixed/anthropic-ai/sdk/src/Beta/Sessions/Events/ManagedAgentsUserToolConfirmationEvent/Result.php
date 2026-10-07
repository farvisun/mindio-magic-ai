<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsUserToolConfirmationEvent;

/**
 * The confirmation result: 'allow' or 'deny'.
 */
enum Result: string
{
    case ALLOW = 'allow';

    case DENY = 'deny';
}
