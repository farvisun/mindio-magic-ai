<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages\WebFetchToolResultBlock;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Messages\WebFetchBlock;
use MindioMagicAI\Vendor\Anthropic\Messages\WebFetchToolResultErrorBlock;

/**
 * @phpstan-import-type WebFetchToolResultErrorBlockShape from \Anthropic\Messages\WebFetchToolResultErrorBlock
 * @phpstan-import-type WebFetchBlockShape from \Anthropic\Messages\WebFetchBlock
 *
 * @phpstan-type ContentVariants = WebFetchToolResultErrorBlock|WebFetchBlock
 * @phpstan-type ContentShape = ContentVariants|WebFetchToolResultErrorBlockShape|WebFetchBlockShape
 */
final class Content implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [WebFetchToolResultErrorBlock::class, WebFetchBlock::class];
    }
}
