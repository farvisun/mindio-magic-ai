<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages\ContentBlockSource;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ListOf;
use MindioMagicAI\Vendor\Anthropic\Messages\ContentBlockSourceContent;
use MindioMagicAI\Vendor\Anthropic\Messages\ImageBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\TextBlockParam;

/**
 * @phpstan-import-type ContentBlockSourceContentShape from \Anthropic\Messages\ContentBlockSourceContent
 *
 * @phpstan-type ContentVariants = string|list<TextBlockParam|ImageBlockParam>
 * @phpstan-type ContentShape = ContentVariants|list<ContentBlockSourceContentShape>
 */
final class Content implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return ['string', new ListOf(ContentBlockSourceContent::class)];
    }
}
