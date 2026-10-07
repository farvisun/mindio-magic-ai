<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type CodeExecutionToolResultErrorShape from \Anthropic\Messages\CodeExecutionToolResultError
 * @phpstan-import-type CodeExecutionResultBlockShape from \Anthropic\Messages\CodeExecutionResultBlock
 * @phpstan-import-type EncryptedCodeExecutionResultBlockShape from \Anthropic\Messages\EncryptedCodeExecutionResultBlock
 *
 * @phpstan-type CodeExecutionToolResultBlockContentVariants = CodeExecutionToolResultError|CodeExecutionResultBlock|EncryptedCodeExecutionResultBlock
 * @phpstan-type CodeExecutionToolResultBlockContentShape = CodeExecutionToolResultBlockContentVariants|CodeExecutionToolResultErrorShape|CodeExecutionResultBlockShape|EncryptedCodeExecutionResultBlockShape
 */
final class CodeExecutionToolResultBlockContent implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [
            CodeExecutionToolResultError::class,
            CodeExecutionResultBlock::class,
            EncryptedCodeExecutionResultBlock::class,
        ];
    }
}
