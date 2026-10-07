<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ListOf;

/**
 * @phpstan-import-type WebSearchResultBlockParamShape from \Anthropic\Messages\WebSearchResultBlockParam
 * @phpstan-import-type WebSearchToolRequestErrorShape from \Anthropic\Messages\WebSearchToolRequestError
 *
 * @phpstan-type WebSearchToolResultBlockParamContentVariants = list<WebSearchResultBlockParam>|WebSearchToolRequestError
 * @phpstan-type WebSearchToolResultBlockParamContentShape = WebSearchToolResultBlockParamContentVariants|list<WebSearchResultBlockParamShape>|WebSearchToolRequestErrorShape
 */
final class WebSearchToolResultBlockParamContent implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [
            WebSearchToolRequestError::class,
            new ListOf(WebSearchResultBlockParam::class),
        ];
    }
}
