<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaMessageParam;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaAdvisorToolResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaBashCodeExecutionToolResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaCodeExecutionToolResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaCompactionBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaContainerUploadBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaContentBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaFallbackBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaImageBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaMCPToolListingBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaMCPToolUseBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRedactedThinkingBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRequestDocumentBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRequestMCPToolResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRequestToolAdditionBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRequestToolRemovalBlock;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaSearchResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaServerToolUseBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaTextBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaTextEditorCodeExecutionToolResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaThinkingBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaToolResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaToolSearchToolResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaToolUseBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaWebFetchToolResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaWebSearchToolResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ListOf;

/**
 * @phpstan-import-type BetaContentBlockParamShape from \Anthropic\Beta\Messages\BetaContentBlockParam
 *
 * @phpstan-type ContentVariants = string|list<BetaTextBlockParam|BetaImageBlockParam|BetaRequestDocumentBlock|BetaSearchResultBlockParam|BetaThinkingBlockParam|BetaRedactedThinkingBlockParam|BetaToolUseBlockParam|BetaToolResultBlockParam|BetaServerToolUseBlockParam|BetaWebSearchToolResultBlockParam|BetaWebFetchToolResultBlockParam|BetaAdvisorToolResultBlockParam|BetaCodeExecutionToolResultBlockParam|BetaBashCodeExecutionToolResultBlockParam|BetaTextEditorCodeExecutionToolResultBlockParam|BetaToolSearchToolResultBlockParam|BetaMCPToolUseBlockParam|BetaRequestMCPToolResultBlockParam|BetaContainerUploadBlockParam|BetaCompactionBlockParam|BetaRequestToolAdditionBlock|BetaRequestToolRemovalBlock|BetaMCPToolListingBlockParam|BetaFallbackBlockParam>
 * @phpstan-type ContentShape = ContentVariants|list<BetaContentBlockParamShape>
 */
final class Content implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return ['string', new ListOf(BetaContentBlockParam::class)];
    }
}
