<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages\ToolSearchToolResultBlockParam;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Messages\ToolSearchToolResultErrorParam;
use MindioMagicAI\Vendor\Anthropic\Messages\ToolSearchToolSearchResultBlockParam;

/**
 * @phpstan-import-type ToolSearchToolResultErrorParamShape from \Anthropic\Messages\ToolSearchToolResultErrorParam
 * @phpstan-import-type ToolSearchToolSearchResultBlockParamShape from \Anthropic\Messages\ToolSearchToolSearchResultBlockParam
 *
 * @phpstan-type ContentVariants = ToolSearchToolResultErrorParam|ToolSearchToolSearchResultBlockParam
 * @phpstan-type ContentShape = ContentVariants|ToolSearchToolResultErrorParamShape|ToolSearchToolSearchResultBlockParamShape
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
            ToolSearchToolResultErrorParam::class,
            ToolSearchToolSearchResultBlockParam::class,
        ];
    }
}
