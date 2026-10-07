<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaWebFetchToolResultBlockParam;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaWebFetchBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaWebFetchToolResultErrorBlockParam;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type BetaWebFetchToolResultErrorBlockParamShape from \Anthropic\Beta\Messages\BetaWebFetchToolResultErrorBlockParam
 * @phpstan-import-type BetaWebFetchBlockParamShape from \Anthropic\Beta\Messages\BetaWebFetchBlockParam
 *
 * @phpstan-type ContentVariants = BetaWebFetchToolResultErrorBlockParam|BetaWebFetchBlockParam
 * @phpstan-type ContentShape = ContentVariants|BetaWebFetchToolResultErrorBlockParamShape|BetaWebFetchBlockParamShape
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
            BetaWebFetchToolResultErrorBlockParam::class,
            BetaWebFetchBlockParam::class,
        ];
    }
}
