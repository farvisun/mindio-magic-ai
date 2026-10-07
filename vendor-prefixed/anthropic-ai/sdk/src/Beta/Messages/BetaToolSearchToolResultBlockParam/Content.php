<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaToolSearchToolResultBlockParam;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaToolSearchToolResultErrorParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaToolSearchToolSearchResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type BetaToolSearchToolResultErrorParamShape from \Anthropic\Beta\Messages\BetaToolSearchToolResultErrorParam
 * @phpstan-import-type BetaToolSearchToolSearchResultBlockParamShape from \Anthropic\Beta\Messages\BetaToolSearchToolSearchResultBlockParam
 *
 * @phpstan-type ContentVariants = BetaToolSearchToolResultErrorParam|BetaToolSearchToolSearchResultBlockParam
 * @phpstan-type ContentShape = ContentVariants|BetaToolSearchToolResultErrorParamShape|BetaToolSearchToolSearchResultBlockParamShape
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
            BetaToolSearchToolResultErrorParam::class,
            BetaToolSearchToolSearchResultBlockParam::class,
        ];
    }
}
