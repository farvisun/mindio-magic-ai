<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages\CitationsDelta;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Messages\CitationCharLocation;
use MindioMagicAI\Vendor\Anthropic\Messages\CitationContentBlockLocation;
use MindioMagicAI\Vendor\Anthropic\Messages\CitationPageLocation;
use MindioMagicAI\Vendor\Anthropic\Messages\CitationsSearchResultLocation;
use MindioMagicAI\Vendor\Anthropic\Messages\CitationsWebSearchResultLocation;

/**
 * @phpstan-import-type CitationCharLocationShape from \Anthropic\Messages\CitationCharLocation
 * @phpstan-import-type CitationPageLocationShape from \Anthropic\Messages\CitationPageLocation
 * @phpstan-import-type CitationContentBlockLocationShape from \Anthropic\Messages\CitationContentBlockLocation
 * @phpstan-import-type CitationsWebSearchResultLocationShape from \Anthropic\Messages\CitationsWebSearchResultLocation
 * @phpstan-import-type CitationsSearchResultLocationShape from \Anthropic\Messages\CitationsSearchResultLocation
 *
 * @phpstan-type CitationVariants = CitationCharLocation|CitationPageLocation|CitationContentBlockLocation|CitationsWebSearchResultLocation|CitationsSearchResultLocation
 * @phpstan-type CitationShape = CitationVariants|CitationCharLocationShape|CitationPageLocationShape|CitationContentBlockLocationShape|CitationsWebSearchResultLocationShape|CitationsSearchResultLocationShape
 */
final class Citation implements ConverterSource
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
            'char_location' => CitationCharLocation::class,
            'page_location' => CitationPageLocation::class,
            'content_block_location' => CitationContentBlockLocation::class,
            'web_search_result_location' => CitationsWebSearchResultLocation::class,
            'search_result_location' => CitationsSearchResultLocation::class,
        ];
    }
}
