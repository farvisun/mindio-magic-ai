<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ListOf;

/**
 * @phpstan-import-type BetaWebSearchResultBlockParamShape from \Anthropic\Beta\Messages\BetaWebSearchResultBlockParam
 * @phpstan-import-type BetaWebSearchToolRequestErrorShape from \Anthropic\Beta\Messages\BetaWebSearchToolRequestError
 *
 * @phpstan-type BetaWebSearchToolResultBlockParamContentVariants = list<BetaWebSearchResultBlockParam>|BetaWebSearchToolRequestError
 * @phpstan-type BetaWebSearchToolResultBlockParamContentShape = BetaWebSearchToolResultBlockParamContentVariants|list<BetaWebSearchResultBlockParamShape>|BetaWebSearchToolRequestErrorShape
 */
final class BetaWebSearchToolResultBlockParamContent implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [
            BetaWebSearchToolRequestError::class,
            new ListOf(BetaWebSearchResultBlockParam::class),
        ];
    }
}
