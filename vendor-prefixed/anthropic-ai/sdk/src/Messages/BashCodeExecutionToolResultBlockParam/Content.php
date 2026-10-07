<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages\BashCodeExecutionToolResultBlockParam;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Messages\BashCodeExecutionResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\BashCodeExecutionToolResultErrorParam;

/**
 * @phpstan-import-type BashCodeExecutionToolResultErrorParamShape from \Anthropic\Messages\BashCodeExecutionToolResultErrorParam
 * @phpstan-import-type BashCodeExecutionResultBlockParamShape from \Anthropic\Messages\BashCodeExecutionResultBlockParam
 *
 * @phpstan-type ContentVariants = BashCodeExecutionToolResultErrorParam|BashCodeExecutionResultBlockParam
 * @phpstan-type ContentShape = ContentVariants|BashCodeExecutionToolResultErrorParamShape|BashCodeExecutionResultBlockParamShape
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
            BashCodeExecutionToolResultErrorParam::class,
            BashCodeExecutionResultBlockParam::class,
        ];
    }
}
