<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Dreams\BetaDreamModelConfigParam;

/**
 * How fast the model generates output for the dream. Defaults to `standard`.
 *
 * Dreams accept only `standard`.
 */
enum Speed: string
{
    case STANDARD = 'standard';

    case FAST = 'fast';
}
