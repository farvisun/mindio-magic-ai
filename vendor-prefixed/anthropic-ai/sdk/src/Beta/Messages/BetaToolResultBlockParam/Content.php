<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaToolResultBlockParam;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaBrowserStateBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaImageBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRequestDocumentBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaSearchResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaTextBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaToolReferenceBlockParam;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ListOf;

/**
 * @phpstan-import-type ContentShape from \Anthropic\Beta\Messages\BetaToolResultBlockParam\Content\Content as ContentShape1
 *
 * @phpstan-type ContentVariants = string|list<BetaTextBlockParam|BetaImageBlockParam|BetaSearchResultBlockParam|BetaRequestDocumentBlock|BetaToolReferenceBlockParam|BetaBrowserStateBlockParam>
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
