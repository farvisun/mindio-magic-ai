<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaContentBlockSource;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaContentBlockSourceContent;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaImageBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaTextBlockParam;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ListOf;

/**
 * @phpstan-import-type BetaContentBlockSourceContentShape from \Anthropic\Beta\Messages\BetaContentBlockSourceContent
 *
 * @phpstan-type ContentVariants = string|list<BetaTextBlockParam|BetaImageBlockParam>
 * @phpstan-type ContentShape = ContentVariants|list<BetaContentBlockSourceContentShape>
 */
final class Content implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return ['string', new ListOf(BetaContentBlockSourceContent::class)];
    }
}
