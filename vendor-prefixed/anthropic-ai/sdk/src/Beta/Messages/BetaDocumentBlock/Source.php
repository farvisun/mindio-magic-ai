<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaDocumentBlock;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaBase64PDFSource;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaPlainTextSource;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type BetaBase64PDFSourceShape from \Anthropic\Beta\Messages\BetaBase64PDFSource
 * @phpstan-import-type BetaPlainTextSourceShape from \Anthropic\Beta\Messages\BetaPlainTextSource
 *
 * @phpstan-type SourceVariants = BetaBase64PDFSource|BetaPlainTextSource
 * @phpstan-type SourceShape = SourceVariants|BetaBase64PDFSourceShape|BetaPlainTextSourceShape
 */
final class Source implements ConverterSource
{
    use SdkUnion;

    public static function discriminator(): string
    {
        return 'type';
    }

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [
            'base64' => BetaBase64PDFSource::class,
            'text' => BetaPlainTextSource::class,
        ];
    }
}
