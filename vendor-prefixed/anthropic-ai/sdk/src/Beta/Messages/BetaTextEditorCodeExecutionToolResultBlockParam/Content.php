<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaTextEditorCodeExecutionToolResultBlockParam;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaTextEditorCodeExecutionCreateResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaTextEditorCodeExecutionStrReplaceResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaTextEditorCodeExecutionToolResultErrorParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaTextEditorCodeExecutionViewResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type BetaTextEditorCodeExecutionToolResultErrorParamShape from \Anthropic\Beta\Messages\BetaTextEditorCodeExecutionToolResultErrorParam
 * @phpstan-import-type BetaTextEditorCodeExecutionViewResultBlockParamShape from \Anthropic\Beta\Messages\BetaTextEditorCodeExecutionViewResultBlockParam
 * @phpstan-import-type BetaTextEditorCodeExecutionCreateResultBlockParamShape from \Anthropic\Beta\Messages\BetaTextEditorCodeExecutionCreateResultBlockParam
 * @phpstan-import-type BetaTextEditorCodeExecutionStrReplaceResultBlockParamShape from \Anthropic\Beta\Messages\BetaTextEditorCodeExecutionStrReplaceResultBlockParam
 *
 * @phpstan-type ContentVariants = BetaTextEditorCodeExecutionToolResultErrorParam|BetaTextEditorCodeExecutionViewResultBlockParam|BetaTextEditorCodeExecutionCreateResultBlockParam|BetaTextEditorCodeExecutionStrReplaceResultBlockParam
 * @phpstan-type ContentShape = ContentVariants|BetaTextEditorCodeExecutionToolResultErrorParamShape|BetaTextEditorCodeExecutionViewResultBlockParamShape|BetaTextEditorCodeExecutionCreateResultBlockParamShape|BetaTextEditorCodeExecutionStrReplaceResultBlockParamShape
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
            BetaTextEditorCodeExecutionToolResultErrorParam::class,
            BetaTextEditorCodeExecutionViewResultBlockParam::class,
            BetaTextEditorCodeExecutionCreateResultBlockParam::class,
            BetaTextEditorCodeExecutionStrReplaceResultBlockParam::class,
        ];
    }
}
