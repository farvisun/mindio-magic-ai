<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaBashCodeExecutionToolResultBlock;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaBashCodeExecutionResultBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaBashCodeExecutionToolResultError;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type BetaBashCodeExecutionToolResultErrorShape from \Anthropic\Beta\Messages\BetaBashCodeExecutionToolResultError
 * @phpstan-import-type BetaBashCodeExecutionResultBlockShape from \Anthropic\Beta\Messages\BetaBashCodeExecutionResultBlock
 *
 * @phpstan-type ContentVariants = BetaBashCodeExecutionToolResultError|BetaBashCodeExecutionResultBlock
 * @phpstan-type ContentShape = ContentVariants|BetaBashCodeExecutionToolResultErrorShape|BetaBashCodeExecutionResultBlockShape
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
            BetaBashCodeExecutionToolResultError::class,
            BetaBashCodeExecutionResultBlock::class,
        ];
    }
}
