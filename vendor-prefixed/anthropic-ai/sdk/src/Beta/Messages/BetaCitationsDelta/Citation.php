<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaCitationsDelta;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaCitationCharLocation;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaCitationContentBlockLocation;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaCitationPageLocation;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaCitationSearchResultLocation;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaCitationsWebSearchResultLocation;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type BetaCitationCharLocationShape from \Anthropic\Beta\Messages\BetaCitationCharLocation
 * @phpstan-import-type BetaCitationPageLocationShape from \Anthropic\Beta\Messages\BetaCitationPageLocation
 * @phpstan-import-type BetaCitationContentBlockLocationShape from \Anthropic\Beta\Messages\BetaCitationContentBlockLocation
 * @phpstan-import-type BetaCitationsWebSearchResultLocationShape from \Anthropic\Beta\Messages\BetaCitationsWebSearchResultLocation
 * @phpstan-import-type BetaCitationSearchResultLocationShape from \Anthropic\Beta\Messages\BetaCitationSearchResultLocation
 *
 * @phpstan-type CitationVariants = BetaCitationCharLocation|BetaCitationPageLocation|BetaCitationContentBlockLocation|BetaCitationsWebSearchResultLocation|BetaCitationSearchResultLocation
 * @phpstan-type CitationShape = CitationVariants|BetaCitationCharLocationShape|BetaCitationPageLocationShape|BetaCitationContentBlockLocationShape|BetaCitationsWebSearchResultLocationShape|BetaCitationSearchResultLocationShape
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
            'char_location' => BetaCitationCharLocation::class,
            'page_location' => BetaCitationPageLocation::class,
            'content_block_location' => BetaCitationContentBlockLocation::class,
            'web_search_result_location' => BetaCitationsWebSearchResultLocation::class,
            'search_result_location' => BetaCitationSearchResultLocation::class,
        ];
    }
}
