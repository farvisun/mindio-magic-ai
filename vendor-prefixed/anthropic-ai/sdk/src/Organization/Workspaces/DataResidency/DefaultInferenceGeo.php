<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\DataResidency;

/**
 * Default inference geo applied when requests omit the parameter.
 */
enum DefaultInferenceGeo: string
{
    case GLOBAL = 'global';

    case US = 'us';
}
