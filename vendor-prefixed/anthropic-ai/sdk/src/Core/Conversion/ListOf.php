<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Core\Conversion;

use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Concerns\ArrayOf;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;

/**
 * @internal
 */
final class ListOf implements Converter
{
    use ArrayOf;

    // @phpstan-ignore-next-line missingType.iterableValue
    private function empty(): array|object
    {
        return [];
    }
}
