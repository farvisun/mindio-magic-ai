<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages\ToolResultBlockParam;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ListOf;
use MindioMagicAI\Vendor\Anthropic\Messages\BrowserStateBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\DocumentBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\ImageBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\SearchResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\TextBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\ToolReferenceBlockParam;

/**
 * @phpstan-import-type ContentShape from \Anthropic\Messages\ToolResultBlockParam\Content\Content as ContentShape1
 *
 * @phpstan-type ContentVariants = string|list<TextBlockParam|ImageBlockParam|SearchResultBlockParam|DocumentBlockParam|ToolReferenceBlockParam|BrowserStateBlockParam>
 * @phpstan-type ContentShape = ContentVariants|list<ContentShape1>
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
            'string',
            new ListOf(
                Content\Content::class
            ),
        ];
    }
}
