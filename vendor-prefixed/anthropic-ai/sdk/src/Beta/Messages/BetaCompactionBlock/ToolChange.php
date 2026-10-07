<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaCompactionBlock;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaResponseToolAdditionBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaResponseToolRemovalBlock;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type BetaResponseToolAdditionBlockShape from \Anthropic\Beta\Messages\BetaResponseToolAdditionBlock
 * @phpstan-import-type BetaResponseToolRemovalBlockShape from \Anthropic\Beta\Messages\BetaResponseToolRemovalBlock
 *
 * @phpstan-type ToolChangeVariants = BetaResponseToolAdditionBlock|BetaResponseToolRemovalBlock
 * @phpstan-type ToolChangeShape = ToolChangeVariants|BetaResponseToolAdditionBlockShape|BetaResponseToolRemovalBlockShape
 */
final class ToolChange implements ConverterSource
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
            'tool_addition' => BetaResponseToolAdditionBlock::class,
            'tool_removal' => BetaResponseToolRemovalBlock::class,
        ];
    }
}
