<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaClearThinking20251015Edit;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaAllThinkingTurns;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaThinkingTurns;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ConstantOf;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * Number of most recent assistant turns to keep thinking blocks for. Older turns will have their thinking blocks removed.
 *
 * @phpstan-import-type BetaThinkingTurnsShape from \Anthropic\Beta\Messages\BetaThinkingTurns
 * @phpstan-import-type BetaAllThinkingTurnsShape from \Anthropic\Beta\Messages\BetaAllThinkingTurns
 *
 * @phpstan-type KeepVariants = 'all'|BetaThinkingTurns|BetaAllThinkingTurns
 * @phpstan-type KeepShape = KeepVariants|BetaThinkingTurnsShape|BetaAllThinkingTurnsShape
 */
final class Keep implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [
            BetaThinkingTurns::class,
            BetaAllThinkingTurns::class,
            new ConstantOf('all'),
        ];
    }
}
