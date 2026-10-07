<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Bedrock\Services;

use MindioMagicAI\Vendor\Anthropic\Bedrock\MantleClient;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseStream;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\Core\Util;
use MindioMagicAI\Vendor\Anthropic\Messages\CacheControlEphemeral;
use MindioMagicAI\Vendor\Anthropic\Messages\ContainerParams;
use MindioMagicAI\Vendor\Anthropic\Messages\DiagnosticsParam;
use MindioMagicAI\Vendor\Anthropic\Messages\Message;
use MindioMagicAI\Vendor\Anthropic\Messages\MessageCreateParams\ServiceTier;
use MindioMagicAI\Vendor\Anthropic\Messages\MessageParam;
use MindioMagicAI\Vendor\Anthropic\Messages\MessageTokensCount;
use MindioMagicAI\Vendor\Anthropic\Messages\Metadata;
use MindioMagicAI\Vendor\Anthropic\Messages\Model;
use MindioMagicAI\Vendor\Anthropic\Messages\OutputConfig;
use MindioMagicAI\Vendor\Anthropic\Messages\RawContentBlockDeltaEvent;
use MindioMagicAI\Vendor\Anthropic\Messages\RawContentBlockStartEvent;
use MindioMagicAI\Vendor\Anthropic\Messages\RawContentBlockStopEvent;
use MindioMagicAI\Vendor\Anthropic\Messages\RawMessageDeltaEvent;
use MindioMagicAI\Vendor\Anthropic\Messages\RawMessageStartEvent;
use MindioMagicAI\Vendor\Anthropic\Messages\RawMessageStopEvent;
use MindioMagicAI\Vendor\Anthropic\Messages\ThinkingConfigAdaptive;
use MindioMagicAI\Vendor\Anthropic\Messages\ThinkingConfigBetweenTools;
use MindioMagicAI\Vendor\Anthropic\Messages\ThinkingConfigDisabled;
use MindioMagicAI\Vendor\Anthropic\Messages\ThinkingConfigEnabled;
use MindioMagicAI\Vendor\Anthropic\Messages\ToolChoiceAny;
use MindioMagicAI\Vendor\Anthropic\Messages\ToolChoiceAuto;
use MindioMagicAI\Vendor\Anthropic\Messages\ToolChoiceNone;
use MindioMagicAI\Vendor\Anthropic\Messages\ToolChoiceTool;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;
use MindioMagicAI\Vendor\Anthropic\ServiceContracts\MessagesContract;

/**
 * @phpstan-import-type MessageCreateParamsContainerShape from \Anthropic\Messages\MessageCreateParamsContainer
 * @phpstan-import-type DiagnosticsParamShape from \Anthropic\Messages\DiagnosticsParam
 * @phpstan-import-type MessageParamShape from \Anthropic\Messages\MessageParam
 * @phpstan-import-type MetadataShape from \Anthropic\Messages\Metadata
 * @phpstan-import-type OutputConfigShape from \Anthropic\Messages\OutputConfig
 * @phpstan-import-type SystemShape from \Anthropic\Messages\MessageCreateParams\System
 * @phpstan-import-type ThinkingConfigParamShape from \Anthropic\Messages\ThinkingConfigParam
 * @phpstan-import-type ToolChoiceShape from \Anthropic\Messages\ToolChoice
 * @phpstan-import-type ToolUnionShape from \Anthropic\Messages\ToolUnion
 * @phpstan-import-type MessageCountTokensToolShape from \Anthropic\Messages\MessageCountTokensTool
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
final class MantleMessagesService implements MessagesContract
{
    /**
     * @api
     */
    public MantleMessagesRawService $raw;

    /**
     * @internal
     */
    public function __construct(MantleClient $client)
    {
        $this->raw = new MantleMessagesRawService($client);
    }

    /**
     * @api
     *
     * @param list<MessageParam|MessageParamShape> $messages
     * @param Model::CLAUDE_OPUS_4_5|Model::CLAUDE_OPUS_4_6|Model::CLAUDE_SONNET_4_5|Model::CLAUDE_HAIKU_4_5 $model
     * @param MessageCreateParamsContainerShape|null $container
     * @param DiagnosticsParam|DiagnosticsParamShape|null $diagnostics
     * @param Metadata|MetadataShape $metadata
     * @param OutputConfig|OutputConfigShape $outputConfig
     * @param list<string> $stopSequences
     * @param SystemShape $system
     * @param ThinkingConfigEnabled|ThinkingConfigDisabled|ThinkingConfigBetweenTools|ThinkingConfigAdaptive|ThinkingConfigParamShape|null $thinking
     * @param ToolChoiceAuto|ToolChoiceAny|ToolChoiceTool|ToolChoiceNone|ToolChoiceShape|null $toolChoice
     * @param list<ToolUnionShape> $tools
     * @param RequestOpts|null $requestOptions
     *
     * @throws APIException
     */
    public function create(
        int $maxTokens,
        array $messages,
        Model|string $model,
        CacheControlEphemeral|array|null $cacheControl = null,
        string|ContainerParams|array|null $container = null,
        DiagnosticsParam|array|null $diagnostics = null,
        ?string $inferenceGeo = null,
        Metadata|array|null $metadata = null,
        OutputConfig|array|null $outputConfig = null,
        ServiceTier|string|null $serviceTier = null,
        ?array $stopSequences = null,
        string|array|null $system = null,
        ?float $temperature = null,
        ThinkingConfigEnabled|array|ThinkingConfigDisabled|ThinkingConfigBetweenTools|ThinkingConfigAdaptive|null $thinking = null,
        ToolChoiceAuto|array|ToolChoiceAny|ToolChoiceTool|ToolChoiceNone|null $toolChoice = null,
        ?array $tools = null,
        ?int $topK = null,
        ?float $topP = null,
        ?string $userProfileID = null,
        ?string $workspaceID = null,
        RequestOptions|array|null $requestOptions = null,
    ): Message {
        $params = Util::removeNulls(
            [
                'maxTokens' => $maxTokens,
                'messages' => $messages,
                'model' => $model,
                'cacheControl' => $cacheControl,
                'container' => $container,
                'diagnostics' => $diagnostics,
                'metadata' => $metadata,
                'outputConfig' => $outputConfig,
                'serviceTier' => $serviceTier,
                'stopSequences' => $stopSequences,
                'system' => $system,
                'temperature' => $temperature,
                'thinking' => $thinking,
                'toolChoice' => $toolChoice,
                'tools' => $tools,
                'topK' => $topK,
                'topP' => $topP,
            ],
        );

        // @phpstan-ignore-next-line argument.type
        $response = $this->raw->create(params: $params, requestOptions: $requestOptions);

        return $response->parse();
    }

    /**
     * @api
     *
     * @param list<MessageParam|MessageParamShape> $messages
     * @param Model::CLAUDE_OPUS_4_5|Model::CLAUDE_OPUS_4_6|Model::CLAUDE_SONNET_4_5|Model::CLAUDE_HAIKU_4_5 $model
     * @param MessageCreateParamsContainerShape|null $container
     * @param DiagnosticsParam|DiagnosticsParamShape|null $diagnostics
     * @param Metadata|MetadataShape $metadata
     * @param OutputConfig|OutputConfigShape $outputConfig
     * @param list<string> $stopSequences
     * @param SystemShape $system
     * @param ThinkingConfigEnabled|ThinkingConfigDisabled|ThinkingConfigBetweenTools|ThinkingConfigAdaptive|ThinkingConfigParamShape|null $thinking
     * @param ToolChoiceAuto|ToolChoiceAny|ToolChoiceTool|ToolChoiceNone|ToolChoiceShape|null $toolChoice
     * @param list<ToolUnionShape> $tools
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseStream<RawMessageStartEvent|RawMessageDeltaEvent|RawMessageStopEvent|RawContentBlockStartEvent|RawContentBlockDeltaEvent|RawContentBlockStopEvent,>
     *
     * @throws APIException
     */
    public function createStream(
        int $maxTokens,
        array $messages,
        Model|string $model,
        CacheControlEphemeral|array|null $cacheControl = null,
        string|ContainerParams|array|null $container = null,
        DiagnosticsParam|array|null $diagnostics = null,
        ?string $inferenceGeo = null,
        Metadata|array|null $metadata = null,
        OutputConfig|array|null $outputConfig = null,
        ServiceTier|string|null $serviceTier = null,
        ?array $stopSequences = null,
        string|array|null $system = null,
        ?float $temperature = null,
        ThinkingConfigEnabled|array|ThinkingConfigDisabled|ThinkingConfigBetweenTools|ThinkingConfigAdaptive|null $thinking = null,
        ToolChoiceAuto|array|ToolChoiceAny|ToolChoiceTool|ToolChoiceNone|null $toolChoice = null,
        ?array $tools = null,
        ?int $topK = null,
        ?float $topP = null,
        ?string $userProfileID = null,
        ?string $workspaceID = null,
        RequestOptions|array|null $requestOptions = null,
    ): BaseStream {
        $params = Util::removeNulls(
            [
                'maxTokens' => $maxTokens,
                'messages' => $messages,
                'model' => $model,
                'cacheControl' => $cacheControl,
                'container' => $container,
                'diagnostics' => $diagnostics,
                'metadata' => $metadata,
                'outputConfig' => $outputConfig,
                'serviceTier' => $serviceTier,
                'stopSequences' => $stopSequences,
                'system' => $system,
                'temperature' => $temperature,
                'thinking' => $thinking,
                'toolChoice' => $toolChoice,
                'tools' => $tools,
                'topK' => $topK,
                'topP' => $topP,
            ],
        );

        // @phpstan-ignore-next-line argument.type
        $response = $this->raw->createStream(params: $params, requestOptions: $requestOptions);

        return $response->parse();
    }

    /**
     * @api
     *
     * @param list<MessageParam|MessageParamShape> $messages
     * @param Model::CLAUDE_OPUS_4_5|Model::CLAUDE_OPUS_4_6|Model::CLAUDE_SONNET_4_5|Model::CLAUDE_HAIKU_4_5 $model
     * @param OutputConfig|OutputConfigShape $outputConfig
     * @param SystemShape $system
     * @param ThinkingConfigEnabled|ThinkingConfigDisabled|ThinkingConfigBetweenTools|ThinkingConfigAdaptive|ThinkingConfigParamShape|null $thinking
     * @param ToolChoiceAuto|ToolChoiceAny|ToolChoiceTool|ToolChoiceNone|ToolChoiceShape|null $toolChoice
     * @param list<MessageCountTokensToolShape> $tools
     * @param RequestOpts|null $requestOptions
     *
     * @throws APIException
     */
    public function countTokens(
        array $messages,
        Model|string $model,
        CacheControlEphemeral|array|null $cacheControl = null,
        OutputConfig|array|null $outputConfig = null,
        string|array|null $system = null,
        ThinkingConfigEnabled|array|ThinkingConfigDisabled|ThinkingConfigBetweenTools|ThinkingConfigAdaptive|null $thinking = null,
        ToolChoiceAuto|array|ToolChoiceAny|ToolChoiceTool|ToolChoiceNone|null $toolChoice = null,
        ?array $tools = null,
        ?string $userProfileID = null,
        ?string $workspaceID = null,
        RequestOptions|array|null $requestOptions = null,
    ): MessageTokensCount {
        $params = Util::removeNulls(
            [
                'messages' => $messages,
                'model' => $model,
                'cacheControl' => $cacheControl,
                'outputConfig' => $outputConfig,
                'system' => $system,
                'thinking' => $thinking,
                'toolChoice' => $toolChoice,
                'tools' => $tools,
            ],
        );

        // @phpstan-ignore-next-line argument.type
        $response = $this->raw->countTokens(params: $params, requestOptions: $requestOptions);

        return $response->parse();
    }
}
