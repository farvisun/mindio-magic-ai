<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages\MessageParam;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ListOf;
use MindioMagicAI\Vendor\Anthropic\Messages\BashCodeExecutionToolResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\CodeExecutionToolResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\ContainerUploadBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\ContentBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\DocumentBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\ImageBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\RedactedThinkingBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\SearchResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\ServerToolUseBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\TextBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\TextEditorCodeExecutionToolResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\ThinkingBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\ToolResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\ToolSearchToolResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\ToolUseBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\WebFetchToolResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Messages\WebSearchToolResultBlockParam;

/**
 * @phpstan-import-type ContentBlockParamShape from \Anthropic\Messages\ContentBlockParam
 *
 * @phpstan-type ContentVariants = string|list<TextBlockParam|ImageBlockParam|DocumentBlockParam|SearchResultBlockParam|ThinkingBlockParam|RedactedThinkingBlockParam|ToolUseBlockParam|ToolResultBlockParam|ServerToolUseBlockParam|WebSearchToolResultBlockParam|WebFetchToolResultBlockParam|CodeExecutionToolResultBlockParam|BashCodeExecutionToolResultBlockParam|TextEditorCodeExecutionToolResultBlockParam|ToolSearchToolResultBlockParam|ContainerUploadBlockParam>
 * @phpstan-type ContentShape = ContentVariants|list<ContentBlockParamShape>
 */
final class Content implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return ['string', new ListOf(ContentBlockParam::class)];
    }
}
