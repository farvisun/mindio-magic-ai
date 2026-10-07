<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsModelConfigParams;

use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsEffortHigh;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsEffortLow;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsEffortMax;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsEffortMedium;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsEffortXhigh;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsModelConfigParams\Effort\BetaManagedAgentsEffortLevel;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * How hard Claude works on each inference call. Accepts a bare level string (`"high"`) or `{"type": "high"}`. On create, omitting it resolves the per-model default; on update, omitting it leaves the stored value unchanged.
 *
 * @phpstan-import-type BetaManagedAgentsEffortLowShape from \Anthropic\Beta\Agents\BetaManagedAgentsEffortLow
 * @phpstan-import-type BetaManagedAgentsEffortMediumShape from \Anthropic\Beta\Agents\BetaManagedAgentsEffortMedium
 * @phpstan-import-type BetaManagedAgentsEffortHighShape from \Anthropic\Beta\Agents\BetaManagedAgentsEffortHigh
 * @phpstan-import-type BetaManagedAgentsEffortXhighShape from \Anthropic\Beta\Agents\BetaManagedAgentsEffortXhigh
 * @phpstan-import-type BetaManagedAgentsEffortMaxShape from \Anthropic\Beta\Agents\BetaManagedAgentsEffortMax
 *
 * @phpstan-type EffortVariants = BetaManagedAgentsEffortLow|BetaManagedAgentsEffortMedium|BetaManagedAgentsEffortHigh|BetaManagedAgentsEffortXhigh|BetaManagedAgentsEffortMax|value-of<BetaManagedAgentsEffortLevel>
 * @phpstan-type EffortShape = EffortVariants|BetaManagedAgentsEffortLowShape|BetaManagedAgentsEffortMediumShape|BetaManagedAgentsEffortHighShape|BetaManagedAgentsEffortXhighShape|BetaManagedAgentsEffortMaxShape
 */
final class Effort implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [
            BetaManagedAgentsEffortLevel::class,
            BetaManagedAgentsEffortLow::class,
            BetaManagedAgentsEffortMedium::class,
            BetaManagedAgentsEffortHigh::class,
            BetaManagedAgentsEffortXhigh::class,
            BetaManagedAgentsEffortMax::class,
        ];
    }
}
