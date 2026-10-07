<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaMCPToolResultBlock;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaTextBlock;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ListOf;

/**
 * @phpstan-import-type BetaTextBlockShape from \Anthropic\Beta\Messages\BetaTextBlock
 *
 * @phpstan-type ContentVariants = string|list<BetaTextBlock>
 * @phpstan-type ContentShape = ContentVariants|list<BetaTextBlockShape>
 */
final class Content implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return ['string', new ListOf(BetaTextBlock::class)];
    }
}
