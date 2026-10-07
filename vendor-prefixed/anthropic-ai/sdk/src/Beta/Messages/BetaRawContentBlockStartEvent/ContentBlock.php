<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRawContentBlockStartEvent;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaAdvisorToolResultBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaBashCodeExecutionToolResultBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaCodeExecutionToolResultBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaCompactionBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaContainerUploadBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaFallbackBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaMCPToolListingBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaMCPToolResultBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaMCPToolUseBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRedactedThinkingBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaServerToolUseBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaTextBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaTextEditorCodeExecutionToolResultBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaThinkingBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaToolSearchToolResultBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaToolUseBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaWebFetchToolResultBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaWebSearchToolResultBlock;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type BetaTextBlockShape from \Anthropic\Beta\Messages\BetaTextBlock
 * @phpstan-import-type BetaThinkingBlockShape from \Anthropic\Beta\Messages\BetaThinkingBlock
 * @phpstan-import-type BetaRedactedThinkingBlockShape from \Anthropic\Beta\Messages\BetaRedactedThinkingBlock
 * @phpstan-import-type BetaToolUseBlockShape from \Anthropic\Beta\Messages\BetaToolUseBlock
 * @phpstan-import-type BetaServerToolUseBlockShape from \Anthropic\Beta\Messages\BetaServerToolUseBlock
 * @phpstan-import-type BetaWebSearchToolResultBlockShape from \Anthropic\Beta\Messages\BetaWebSearchToolResultBlock
 * @phpstan-import-type BetaWebFetchToolResultBlockShape from \Anthropic\Beta\Messages\BetaWebFetchToolResultBlock
 * @phpstan-import-type BetaAdvisorToolResultBlockShape from \Anthropic\Beta\Messages\BetaAdvisorToolResultBlock
 * @phpstan-import-type BetaCodeExecutionToolResultBlockShape from \Anthropic\Beta\Messages\BetaCodeExecutionToolResultBlock
 * @phpstan-import-type BetaBashCodeExecutionToolResultBlockShape from \Anthropic\Beta\Messages\BetaBashCodeExecutionToolResultBlock
 * @phpstan-import-type BetaTextEditorCodeExecutionToolResultBlockShape from \Anthropic\Beta\Messages\BetaTextEditorCodeExecutionToolResultBlock
 * @phpstan-import-type BetaToolSearchToolResultBlockShape from \Anthropic\Beta\Messages\BetaToolSearchToolResultBlock
 * @phpstan-import-type BetaMCPToolUseBlockShape from \Anthropic\Beta\Messages\BetaMCPToolUseBlock
 * @phpstan-import-type BetaMCPToolResultBlockShape from \Anthropic\Beta\Messages\BetaMCPToolResultBlock
 * @phpstan-import-type BetaContainerUploadBlockShape from \Anthropic\Beta\Messages\BetaContainerUploadBlock
 * @phpstan-import-type BetaCompactionBlockShape from \Anthropic\Beta\Messages\BetaCompactionBlock
 * @phpstan-import-type BetaFallbackBlockShape from \Anthropic\Beta\Messages\BetaFallbackBlock
 * @phpstan-import-type BetaMCPToolListingBlockShape from \Anthropic\Beta\Messages\BetaMCPToolListingBlock
 *
 * @phpstan-type ContentBlockVariants = BetaTextBlock|BetaThinkingBlock|BetaRedactedThinkingBlock|BetaToolUseBlock|BetaServerToolUseBlock|BetaWebSearchToolResultBlock|BetaWebFetchToolResultBlock|BetaAdvisorToolResultBlock|BetaCodeExecutionToolResultBlock|BetaBashCodeExecutionToolResultBlock|BetaTextEditorCodeExecutionToolResultBlock|BetaToolSearchToolResultBlock|BetaMCPToolUseBlock|BetaMCPToolResultBlock|BetaContainerUploadBlock|BetaCompactionBlock|BetaFallbackBlock|BetaMCPToolListingBlock
 * @phpstan-type ContentBlockShape = ContentBlockVariants|BetaTextBlockShape|BetaThinkingBlockShape|BetaRedactedThinkingBlockShape|BetaToolUseBlockShape|BetaServerToolUseBlockShape|BetaWebSearchToolResultBlockShape|BetaWebFetchToolResultBlockShape|BetaAdvisorToolResultBlockShape|BetaCodeExecutionToolResultBlockShape|BetaBashCodeExecutionToolResultBlockShape|BetaTextEditorCodeExecutionToolResultBlockShape|BetaToolSearchToolResultBlockShape|BetaMCPToolUseBlockShape|BetaMCPToolResultBlockShape|BetaContainerUploadBlockShape|BetaCompactionBlockShape|BetaFallbackBlockShape|BetaMCPToolListingBlockShape
 */
final class ContentBlock implements ConverterSource
{
    use SdkUnion;

    public static function discriminator(): string
    {
        return 'type';
    }

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [
            'text' => BetaTextBlock::class,
            'thinking' => BetaThinkingBlock::class,
            'redacted_thinking' => BetaRedactedThinkingBlock::class,
            'tool_use' => BetaToolUseBlock::class,
            'server_tool_use' => BetaServerToolUseBlock::class,
            'web_search_tool_result' => BetaWebSearchToolResultBlock::class,
            'web_fetch_tool_result' => BetaWebFetchToolResultBlock::class,
            'advisor_tool_result' => BetaAdvisorToolResultBlock::class,
            'code_execution_tool_result' => BetaCodeExecutionToolResultBlock::class,
            'bash_code_execution_tool_result' => BetaBashCodeExecutionToolResultBlock::class,
            'text_editor_code_execution_tool_result' => BetaTextEditorCodeExecutionToolResultBlock::class,
            'tool_search_tool_result' => BetaToolSearchToolResultBlock::class,
            'mcp_tool_use' => BetaMCPToolUseBlock::class,
            'mcp_tool_result' => BetaMCPToolResultBlock::class,
            'container_upload' => BetaContainerUploadBlock::class,
            'compaction' => BetaCompactionBlock::class,
            'fallback' => BetaFallbackBlock::class,
            'mcp_tool_listing' => BetaMCPToolListingBlock::class,
        ];
    }
}
