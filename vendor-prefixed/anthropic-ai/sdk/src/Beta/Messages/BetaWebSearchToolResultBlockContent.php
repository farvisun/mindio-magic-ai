<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ListOf;

/**
 * @phpstan-import-type BetaWebSearchToolResultErrorShape from \Anthropic\Beta\Messages\BetaWebSearchToolResultError
 * @phpstan-import-type BetaWebSearchResultBlockShape from \Anthropic\Beta\Messages\BetaWebSearchResultBlock
 *
 * @phpstan-type BetaWebSearchToolResultBlockContentVariants = BetaWebSearchToolResultError|list<BetaWebSearchResultBlock>
 * @phpstan-type BetaWebSearchToolResultBlockContentShape = BetaWebSearchToolResultBlockContentVariants|BetaWebSearchToolResultErrorShape|list<BetaWebSearchResultBlockShape>
 */
final class BetaWebSearchToolResultBlockContent implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [
            BetaWebSearchToolResultError::class,
            new ListOf(BetaWebSearchResultBlock::class),
        ];
    }
}
