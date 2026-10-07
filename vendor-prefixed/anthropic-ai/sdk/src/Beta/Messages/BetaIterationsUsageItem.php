<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type BetaMessageIterationUsageShape from \Anthropic\Beta\Messages\BetaMessageIterationUsage
 * @phpstan-import-type BetaCompactionIterationUsageShape from \Anthropic\Beta\Messages\BetaCompactionIterationUsage
 * @phpstan-import-type BetaAdvisorMessageIterationUsageShape from \Anthropic\Beta\Messages\BetaAdvisorMessageIterationUsage
 * @phpstan-import-type BetaFallbackMessageIterationUsageShape from \Anthropic\Beta\Messages\BetaFallbackMessageIterationUsage
 *
 * @phpstan-type BetaIterationsUsageItemVariants = BetaMessageIterationUsage|BetaCompactionIterationUsage|BetaAdvisorMessageIterationUsage|BetaFallbackMessageIterationUsage
 * @phpstan-type BetaIterationsUsageItemShape = BetaIterationsUsageItemVariants|BetaMessageIterationUsageShape|BetaCompactionIterationUsageShape|BetaAdvisorMessageIterationUsageShape|BetaFallbackMessageIterationUsageShape
 */
final class BetaIterationsUsageItem implements ConverterSource
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
            'message' => BetaMessageIterationUsage::class,
            'compaction' => BetaCompactionIterationUsage::class,
            'advisor_message' => BetaAdvisorMessageIterationUsage::class,
            'fallback_message' => BetaFallbackMessageIterationUsage::class,
        ];
    }
}
