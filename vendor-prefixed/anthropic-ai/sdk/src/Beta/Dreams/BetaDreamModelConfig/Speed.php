<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Dreams\BetaDreamModelConfig;

/**
 * How fast the model generates output for the dream. Always `standard`.
 */
enum Speed: string
{
    case STANDARD = 'standard';

    case FAST = 'fast';
}
