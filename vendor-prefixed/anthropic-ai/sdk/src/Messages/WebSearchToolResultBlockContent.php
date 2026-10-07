<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ListOf;

/**
 * @phpstan-import-type WebSearchToolResultErrorShape from \Anthropic\Messages\WebSearchToolResultError
 * @phpstan-import-type WebSearchResultBlockShape from \Anthropic\Messages\WebSearchResultBlock
 *
 * @phpstan-type WebSearchToolResultBlockContentVariants = WebSearchToolResultError|list<WebSearchResultBlock>
 * @phpstan-type WebSearchToolResultBlockContentShape = WebSearchToolResultBlockContentVariants|WebSearchToolResultErrorShape|list<WebSearchResultBlockShape>
 */
final class WebSearchToolResultBlockContent implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [
            WebSearchToolResultError::class, new ListOf(WebSearchResultBlock::class),
        ];
    }
}
