<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaAdvisorToolResultBlock;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaAdvisorRedactedResultBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaAdvisorResultBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaAdvisorToolResultError;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type BetaAdvisorToolResultErrorShape from \Anthropic\Beta\Messages\BetaAdvisorToolResultError
 * @phpstan-import-type BetaAdvisorResultBlockShape from \Anthropic\Beta\Messages\BetaAdvisorResultBlock
 * @phpstan-import-type BetaAdvisorRedactedResultBlockShape from \Anthropic\Beta\Messages\BetaAdvisorRedactedResultBlock
 *
 * @phpstan-type ContentVariants = BetaAdvisorToolResultError|BetaAdvisorResultBlock|BetaAdvisorRedactedResultBlock
 * @phpstan-type ContentShape = ContentVariants|BetaAdvisorToolResultErrorShape|BetaAdvisorResultBlockShape|BetaAdvisorRedactedResultBlockShape
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
            BetaAdvisorToolResultError::class,
            BetaAdvisorResultBlock::class,
            BetaAdvisorRedactedResultBlock::class,
        ];
    }
}
