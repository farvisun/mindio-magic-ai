<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ConstantOf;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ListOf;

/**
 * Opt-in server-side retry on one or more substitute models when the requested model declines for policy reasons. Tried in order: if the first entry also declines, the second is tried, and so on. The string "default" requests the requested model's server-defined default fallback configuration.
 *
 * @phpstan-import-type BetaFallbackParamShape from \Anthropic\Beta\Messages\BetaFallbackParam
 *
 * @phpstan-type BetaFallbacksParamVariants = 'default'|list<BetaFallbackParam>
 * @phpstan-type BetaFallbacksParamShape = BetaFallbacksParamVariants|list<BetaFallbackParamShape>
 */
final class BetaFallbacksParam implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [new ListOf(BetaFallbackParam::class), new ConstantOf('default')];
    }
}
