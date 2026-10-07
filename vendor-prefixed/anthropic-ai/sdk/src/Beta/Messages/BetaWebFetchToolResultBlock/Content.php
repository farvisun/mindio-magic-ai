<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaWebFetchToolResultBlock;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaWebFetchBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaWebFetchToolResultErrorBlock;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type BetaWebFetchToolResultErrorBlockShape from \Anthropic\Beta\Messages\BetaWebFetchToolResultErrorBlock
 * @phpstan-import-type BetaWebFetchBlockShape from \Anthropic\Beta\Messages\BetaWebFetchBlock
 *
 * @phpstan-type ContentVariants = BetaWebFetchToolResultErrorBlock|BetaWebFetchBlock
 * @phpstan-type ContentShape = ContentVariants|BetaWebFetchToolResultErrorBlockShape|BetaWebFetchBlockShape
 */
final class Content implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [BetaWebFetchToolResultErrorBlock::class, BetaWebFetchBlock::class];
    }
}
