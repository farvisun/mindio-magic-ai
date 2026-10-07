<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsModelConfig;

use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsEffortHigh;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsEffortLow;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsEffortMax;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsEffortMedium;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsEffortXhigh;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * How hard Claude works on each inference call. One of `low`, `medium`, `high`, `xhigh`, `max`. Always present; resolved to the per-model default at save time when not supplied.
 *
 * @phpstan-import-type BetaManagedAgentsEffortLowShape from \Anthropic\Beta\Agents\BetaManagedAgentsEffortLow
 * @phpstan-import-type BetaManagedAgentsEffortMediumShape from \Anthropic\Beta\Agents\BetaManagedAgentsEffortMedium
 * @phpstan-import-type BetaManagedAgentsEffortHighShape from \Anthropic\Beta\Agents\BetaManagedAgentsEffortHigh
 * @phpstan-import-type BetaManagedAgentsEffortXhighShape from \Anthropic\Beta\Agents\BetaManagedAgentsEffortXhigh
 * @phpstan-import-type BetaManagedAgentsEffortMaxShape from \Anthropic\Beta\Agents\BetaManagedAgentsEffortMax
 *
 * @phpstan-type EffortVariants = BetaManagedAgentsEffortLow|BetaManagedAgentsEffortMedium|BetaManagedAgentsEffortHigh|BetaManagedAgentsEffortXhigh|BetaManagedAgentsEffortMax
 * @phpstan-type EffortShape = EffortVariants|BetaManagedAgentsEffortLowShape|BetaManagedAgentsEffortMediumShape|BetaManagedAgentsEffortHighShape|BetaManagedAgentsEffortXhighShape|BetaManagedAgentsEffortMaxShape
 */
final class Effort implements ConverterSource
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
            'low' => BetaManagedAgentsEffortLow::class,
            'medium' => BetaManagedAgentsEffortMedium::class,
            'high' => BetaManagedAgentsEffortHigh::class,
            'xhigh' => BetaManagedAgentsEffortXhigh::class,
            'max' => BetaManagedAgentsEffortMax::class,
        ];
    }
}
