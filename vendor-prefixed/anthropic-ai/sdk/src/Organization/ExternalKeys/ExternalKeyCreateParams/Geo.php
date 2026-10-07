<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Organization\ExternalKeys\ExternalKeyCreateParams;

/**
 * Data residency geo. Only `us` is supported.
 */
enum Geo: string
{
    case US = 'us';
}
