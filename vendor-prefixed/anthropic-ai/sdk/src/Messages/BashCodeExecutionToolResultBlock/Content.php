<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages\BashCodeExecutionToolResultBlock;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Messages\BashCodeExecutionResultBlock;
use MindioMagicAI\Vendor\Anthropic\Messages\BashCodeExecutionToolResultError;

/**
 * @phpstan-import-type BashCodeExecutionToolResultErrorShape from \Anthropic\Messages\BashCodeExecutionToolResultError
 * @phpstan-import-type BashCodeExecutionResultBlockShape from \Anthropic\Messages\BashCodeExecutionResultBlock
 *
 * @phpstan-type ContentVariants = BashCodeExecutionToolResultError|BashCodeExecutionResultBlock
 * @phpstan-type ContentShape = ContentVariants|BashCodeExecutionToolResultErrorShape|BashCodeExecutionResultBlockShape
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
            BashCodeExecutionToolResultError::class,
            BashCodeExecutionResultBlock::class,
        ];
    }
}
