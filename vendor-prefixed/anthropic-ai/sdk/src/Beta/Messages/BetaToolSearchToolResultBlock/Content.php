<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaToolSearchToolResultBlock;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaToolSearchToolResultError;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaToolSearchToolSearchResultBlock;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type BetaToolSearchToolResultErrorShape from \Anthropic\Beta\Messages\BetaToolSearchToolResultError
 * @phpstan-import-type BetaToolSearchToolSearchResultBlockShape from \Anthropic\Beta\Messages\BetaToolSearchToolSearchResultBlock
 *
 * @phpstan-type ContentVariants = BetaToolSearchToolResultError|BetaToolSearchToolSearchResultBlock
 * @phpstan-type ContentShape = ContentVariants|BetaToolSearchToolResultErrorShape|BetaToolSearchToolSearchResultBlockShape
 */
final class Content implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [
            BetaToolSearchToolResultError::class,
            BetaToolSearchToolSearchResultBlock::class,
        ];
    }
}
