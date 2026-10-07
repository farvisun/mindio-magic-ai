<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaTextEditorCodeExecutionToolResultBlock;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaTextEditorCodeExecutionCreateResultBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaTextEditorCodeExecutionStrReplaceResultBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaTextEditorCodeExecutionToolResultError;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaTextEditorCodeExecutionViewResultBlock;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type BetaTextEditorCodeExecutionToolResultErrorShape from \Anthropic\Beta\Messages\BetaTextEditorCodeExecutionToolResultError
 * @phpstan-import-type BetaTextEditorCodeExecutionViewResultBlockShape from \Anthropic\Beta\Messages\BetaTextEditorCodeExecutionViewResultBlock
 * @phpstan-import-type BetaTextEditorCodeExecutionCreateResultBlockShape from \Anthropic\Beta\Messages\BetaTextEditorCodeExecutionCreateResultBlock
 * @phpstan-import-type BetaTextEditorCodeExecutionStrReplaceResultBlockShape from \Anthropic\Beta\Messages\BetaTextEditorCodeExecutionStrReplaceResultBlock
 *
 * @phpstan-type ContentVariants = BetaTextEditorCodeExecutionToolResultError|BetaTextEditorCodeExecutionViewResultBlock|BetaTextEditorCodeExecutionCreateResultBlock|BetaTextEditorCodeExecutionStrReplaceResultBlock
 * @phpstan-type ContentShape = ContentVariants|BetaTextEditorCodeExecutionToolResultErrorShape|BetaTextEditorCodeExecutionViewResultBlockShape|BetaTextEditorCodeExecutionCreateResultBlockShape|BetaTextEditorCodeExecutionStrReplaceResultBlockShape
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
            BetaTextEditorCodeExecutionToolResultError::class,
            BetaTextEditorCodeExecutionViewResultBlock::class,
            BetaTextEditorCodeExecutionCreateResultBlock::class,
            BetaTextEditorCodeExecutionStrReplaceResultBlock::class,
        ];
    }
}
