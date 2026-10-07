<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsModelConfigParams;

/**
 * Inference speed mode. Defaults to `standard`.
 */
enum Speed: string
{
    case STANDARD = 'standard';

    case FAST = 'fast';
}
