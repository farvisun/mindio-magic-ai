<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Core\Conversion;

use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Concerns\ArrayOf;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;

/**
 * @internal
 */
final class MapOf implements Converter
{
    use ArrayOf;
}
