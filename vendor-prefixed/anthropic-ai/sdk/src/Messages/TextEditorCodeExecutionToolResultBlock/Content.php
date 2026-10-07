<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages\TextEditorCodeExecutionToolResultBlock;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Messages\TextEditorCodeExecutionCreateResultBlock;
use MindioMagicAI\Vendor\Anthropic\Messages\TextEditorCodeExecutionStrReplaceResultBlock;
use MindioMagicAI\Vendor\Anthropic\Messages\TextEditorCodeExecutionToolResultError;
use MindioMagicAI\Vendor\Anthropic\Messages\TextEditorCodeExecutionViewResultBlock;

/**
 * @phpstan-import-type TextEditorCodeExecutionToolResultErrorShape from \Anthropic\Messages\TextEditorCodeExecutionToolResultError
 * @phpstan-import-type TextEditorCodeExecutionViewResultBlockShape from \Anthropic\Messages\TextEditorCodeExecutionViewResultBlock
 * @phpstan-import-type TextEditorCodeExecutionCreateResultBlockShape from \Anthropic\Messages\TextEditorCodeExecutionCreateResultBlock
 * @phpstan-import-type TextEditorCodeExecutionStrReplaceResultBlockShape from \Anthropic\Messages\TextEditorCodeExecutionStrReplaceResultBlock
 *
 * @phpstan-type ContentVariants = TextEditorCodeExecutionToolResultError|TextEditorCodeExecutionViewResultBlock|TextEditorCodeExecutionCreateResultBlock|TextEditorCodeExecutionStrReplaceResultBlock
 * @phpstan-type ContentShape = ContentVariants|TextEditorCodeExecutionToolResultErrorShape|TextEditorCodeExecutionViewResultBlockShape|TextEditorCodeExecutionCreateResultBlockShape|TextEditorCodeExecutionStrReplaceResultBlockShape
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
            TextEditorCodeExecutionToolResultError::class,
            TextEditorCodeExecutionViewResultBlock::class,
            TextEditorCodeExecutionCreateResultBlock::class,
            TextEditorCodeExecutionStrReplaceResultBlock::class,
        ];
    }
}
