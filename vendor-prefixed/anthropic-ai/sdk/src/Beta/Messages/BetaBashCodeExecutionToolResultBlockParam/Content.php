<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaBashCodeExecutionToolResultBlockParam;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaBashCodeExecutionResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaBashCodeExecutionToolResultErrorParam;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type BetaBashCodeExecutionToolResultErrorParamShape from \Anthropic\Beta\Messages\BetaBashCodeExecutionToolResultErrorParam
 * @phpstan-import-type BetaBashCodeExecutionResultBlockParamShape from \Anthropic\Beta\Messages\BetaBashCodeExecutionResultBlockParam
 *
 * @phpstan-type ContentVariants = BetaBashCodeExecutionToolResultErrorParam|BetaBashCodeExecutionResultBlockParam
 * @phpstan-type ContentShape = ContentVariants|BetaBashCodeExecutionToolResultErrorParamShape|BetaBashCodeExecutionResultBlockParamShape
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
            BetaBashCodeExecutionToolResultErrorParam::class,
            BetaBashCodeExecutionResultBlockParam::class,
        ];
    }
}
