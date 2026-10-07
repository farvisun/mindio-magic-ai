<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages\TextEditorCodeExecutionToolResultBlockParam;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Messages\TextEditorCodeExecutionCreateResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\TextEditorCodeExecutionStrReplaceResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\TextEditorCodeExecutionToolResultErrorParam;
use MindioMagicAI\Vendor\Anthropic\Messages\TextEditorCodeExecutionViewResultBlockParam;

/**
 * @phpstan-import-type TextEditorCodeExecutionToolResultErrorParamShape from \Anthropic\Messages\TextEditorCodeExecutionToolResultErrorParam
 * @phpstan-import-type TextEditorCodeExecutionViewResultBlockParamShape from \Anthropic\Messages\TextEditorCodeExecutionViewResultBlockParam
 * @phpstan-import-type TextEditorCodeExecutionCreateResultBlockParamShape from \Anthropic\Messages\TextEditorCodeExecutionCreateResultBlockParam
 * @phpstan-import-type TextEditorCodeExecutionStrReplaceResultBlockParamShape from \Anthropic\Messages\TextEditorCodeExecutionStrReplaceResultBlockParam
 *
 * @phpstan-type ContentVariants = TextEditorCodeExecutionToolResultErrorParam|TextEditorCodeExecutionViewResultBlockParam|TextEditorCodeExecutionCreateResultBlockParam|TextEditorCodeExecutionStrReplaceResultBlockParam
 * @phpstan-type ContentShape = ContentVariants|TextEditorCodeExecutionToolResultErrorParamShape|TextEditorCodeExecutionViewResultBlockParamShape|TextEditorCodeExecutionCreateResultBlockParamShape|TextEditorCodeExecutionStrReplaceResultBlockParamShape
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
            TextEditorCodeExecutionToolResultErrorParam::class,
            TextEditorCodeExecutionViewResultBlockParam::class,
            TextEditorCodeExecutionCreateResultBlockParam::class,
            TextEditorCodeExecutionStrReplaceResultBlockParam::class,
        ];
    }
}
