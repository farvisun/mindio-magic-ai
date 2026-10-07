<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Services\Beta;

use MindioMagicAI\Vendor\Anthropic\Beta\AnthropicBeta;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaCacheControlEphemeral;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaCompactionConfig;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaContextManagementConfig;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaDiagnosticsParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaJSONOutputFormat;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaMessage;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaMessageParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaMessageTokensCount;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaMetadata;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaOutputConfig;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRawContentBlockDeltaEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRawContentBlockStartEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRawContentBlockStopEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRawMessageDeltaEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRawMessageStartEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRawMessageStopEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRawMessageStreamEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRequestMCPServerURLDefinition;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\MessageCountTokensParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\MessageCreateParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\MessageCreateParams\ServiceTier;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\MessageCreateParams\Speed;
use MindioMagicAI\Vendor\Anthropic\Client;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseStream;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\Core\Util;
use MindioMagicAI\Vendor\Anthropic\Messages\Model;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;
use MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\MessagesRawContract;
use MindioMagicAI\Vendor\Anthropic\SSEStream;

/**
 * @phpstan-import-type SystemShape from \Anthropic\Beta\Messages\MessageCountTokensParams\System
 * @phpstan-import-type ToolShape from \Anthropic\Beta\Messages\MessageCountTokensParams\Tool
 * @phpstan-import-type BetaMessageParamShape from \Anthropic\Beta\Messages\BetaMessageParam
 * @phpstan-import-type BetaCacheControlEphemeralShape from \Anthropic\Beta\Messages\BetaCacheControlEphemeral
 * @phpstan-import-type BetaCompactionConfigShape from \Anthropic\Beta\Messages\BetaCompactionConfig
 * @phpstan-import-type ContainerShape from \Anthropic\Beta\Messages\MessageCreateParams\Container
 * @phpstan-import-type BetaContextManagementConfigShape from \Anthropic\Beta\Messages\BetaContextManagementConfig
 * @phpstan-import-type BetaDiagnosticsParamShape from \Anthropic\Beta\Messages\BetaDiagnosticsParam
 * @phpstan-import-type FallbackCreditTokenShape from \Anthropic\Beta\Messages\MessageCreateParams\FallbackCreditToken
 * @phpstan-import-type BetaFallbacksParamShape from \Anthropic\Beta\Messages\BetaFallbacksParam
 * @phpstan-import-type BetaRequestMCPServerURLDefinitionShape from \Anthropic\Beta\Messages\BetaRequestMCPServerURLDefinition
 * @phpstan-import-type BetaMetadataShape from \Anthropic\Beta\Messages\BetaMetadata
 * @phpstan-import-type BetaOutputConfigShape from \Anthropic\Beta\Messages\BetaOutputConfig
 * @phpstan-import-type BetaJSONOutputFormatShape from \Anthropic\Beta\Messages\BetaJSONOutputFormat
 * @phpstan-import-type SystemShape from \Anthropic\Beta\Messages\MessageCreateParams\System as SystemShape1
 * @phpstan-import-type BetaThinkingConfigParamShape from \Anthropic\Beta\Messages\BetaThinkingConfigParam
 * @phpstan-import-type BetaToolChoiceShape from \Anthropic\Beta\Messages\BetaToolChoice
 * @phpstan-import-type BetaToolUnionShape from \Anthropic\Beta\Messages\BetaToolUnion
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
final class MessagesRawService implements MessagesRawContract
{
    // @phpstan-ignore-next-line
    /**
     * @internal
     */
    public function __construct(private Client $client) {}

    /**
     * @api
     *
     * Send a structured list of input messages with text and/or image content, and the model will generate the next message in the conversation.
     *
     * The Messages API can be used for either single queries or stateless multi-turn conversations.
     *
     * Learn more about the Messages API in our [user guide](https://platform.claude.com/docs/en/get-started)
     *
     * @param array{
     *   maxTokens: int,
     *   messages: list<BetaMessageParam|BetaMessageParamShape>,
     *   model: string|Model|value-of<Model>,
     *   cacheControl?: BetaCacheControlEphemeral|BetaCacheControlEphemeralShape|null,
     *   compaction?: BetaCompactionConfig|BetaCompactionConfigShape|null,
     *   container?: ContainerShape|null,
     *   contextManagement?: BetaContextManagementConfig|BetaContextManagementConfigShape|null,
     *   diagnostics?: BetaDiagnosticsParam|BetaDiagnosticsParamShape|null,
     *   fallbackCreditToken?: FallbackCreditTokenShape|null,
     *   fallbacks?: BetaFallbacksParamShape|null,
     *   inferenceGeo?: string|null,
     *   mcpServers?: list<BetaRequestMCPServerURLDefinition|BetaRequestMCPServerURLDefinitionShape>,
     *   metadata?: BetaMetadata|BetaMetadataShape,
     *   outputConfig?: BetaOutputConfig|BetaOutputConfigShape,
     *   outputFormat?: BetaJSONOutputFormat|BetaJSONOutputFormatShape|null,
     *   serviceTier?: ServiceTier|value-of<ServiceTier>,
     *   speed?: Speed|value-of<Speed>|null,
     *   stopSequences?: list<string>,
     *   system?: SystemShape1,
     *   temperature?: float,
     *   thinking?: BetaThinkingConfigParamShape,
     *   toolChoice?: BetaToolChoiceShape,
     *   tools?: list<BetaToolUnionShape>,
     *   topK?: int,
     *   topP?: float,
     *   betas?: list<string|AnthropicBeta|value-of<AnthropicBeta>>,
     *   userProfileID?: string,
     *   workspaceID?: string,
     * }|MessageCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaMessage>
     *
     * @throws APIException
     */
    public function create(
        array|MessageCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse {
        [$parsed, $options] = MessageCreateParams::parseRequest(
            $params,
            $requestOptions,
        );
        $header_params = [
            'betas' => 'anthropic-beta',
            'userProfileID' => 'anthropic-user-profile-id',
            'workspaceID' => 'anthropic-workspace-id',
        ];

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'post',
            path: 'v1/messages?beta=true',
            headers: Util::array_transform_keys(
                array_intersect_key($parsed, array_flip(array_keys($header_params))),
                $header_params,
            ),
            body: (object) array_diff_key(
                $parsed,
                array_flip(array_keys($header_params))
            ),
            options: $options,
            convert: BetaMessage::class,
        );
    }

    /**
     * @api
     *
     * @param array{
     *   maxTokens: int,
     *   messages: list<BetaMessageParam|BetaMessageParamShape>,
     *   model: string|Model|value-of<Model>,
     *   cacheControl?: BetaCacheControlEphemeral|BetaCacheControlEphemeralShape|null,
     *   compaction?: BetaCompactionConfig|BetaCompactionConfigShape|null,
     *   container?: ContainerShape|null,
     *   contextManagement?: BetaContextManagementConfig|BetaContextManagementConfigShape|null,
     *   diagnostics?: BetaDiagnosticsParam|BetaDiagnosticsParamShape|null,
     *   fallbackCreditToken?: FallbackCreditTokenShape|null,
     *   fallbacks?: BetaFallbacksParamShape|null,
     *   inferenceGeo?: string|null,
     *   mcpServers?: list<BetaRequestMCPServerURLDefinition|BetaRequestMCPServerURLDefinitionShape>,
     *   metadata?: BetaMetadata|BetaMetadataShape,
     *   outputConfig?: BetaOutputConfig|BetaOutputConfigShape,
     *   outputFormat?: BetaJSONOutputFormat|BetaJSONOutputFormatShape|null,
     *   serviceTier?: ServiceTier|value-of<ServiceTier>,
     *   speed?: Speed|value-of<Speed>|null,
     *   stopSequences?: list<string>,
     *   system?: SystemShape1,
     *   temperature?: float,
     *   thinking?: BetaThinkingConfigParamShape,
     *   toolChoice?: BetaToolChoiceShape,
     *   tools?: list<BetaToolUnionShape>,
     *   topK?: int,
     *   topP?: float,
     *   betas?: list<string|AnthropicBeta|value-of<AnthropicBeta>>,
     *   userProfileID?: string,
     *   workspaceID?: string,
     * }|MessageCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BaseStream<BetaRawMessageStartEvent|BetaRawMessageDeltaEvent|BetaRawMessageStopEvent|BetaRawContentBlockStartEvent|BetaRawContentBlockDeltaEvent|BetaRawContentBlockStopEvent,>,>
     *
     * @throws APIException
     */
    public function createStream(
        array|MessageCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse {
        [$parsed, $options] = MessageCreateParams::parseRequest(
            $params,
            $requestOptions,
        );
        $parsed['stream'] = true;
        $header_params = [
            'betas' => 'anthropic-beta',
            'userProfileID' => 'anthropic-user-profile-id',
            'workspaceID' => 'anthropic-workspace-id',
        ];

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'post',
            path: 'v1/messages?beta=true',
            headers: Util::array_transform_keys(
                [
                    'Accept' => 'text/event-stream',
                    ...array_intersect_key(
                        $parsed,
                        array_flip(array_keys($header_params))
                    ),
                ],
                $header_params,
            ),
            body: (object) array_diff_key(
                $parsed,
                array_flip(array_keys($header_params))
            ),
            options: $options,
            convert: BetaRawMessageStreamEvent::class,
            stream: SSEStream::class,
        );
    }

    /**
     * @api
     *
     * Count the number of tokens in a Message.
     *
     * The Token Count API can be used to count the number of tokens in a Message, including tools, images, and documents, without creating it.
     *
     * Learn more about token counting in our [user guide](https://platform.claude.com/docs/en/build-with-claude/token-counting)
     *
     * @param array{
     *   messages: list<BetaMessageParam|BetaMessageParamShape>,
     *   model: string|Model|value-of<Model>,
     *   cacheControl?: BetaCacheControlEphemeral|BetaCacheControlEphemeralShape|null,
     *   compaction?: BetaCompactionConfig|BetaCompactionConfigShape|null,
     *   contextManagement?: BetaContextManagementConfig|BetaContextManagementConfigShape|null,
     *   mcpServers?: list<BetaRequestMCPServerURLDefinition|BetaRequestMCPServerURLDefinitionShape>,
     *   outputConfig?: BetaOutputConfig|BetaOutputConfigShape,
     *   outputFormat?: BetaJSONOutputFormat|BetaJSONOutputFormatShape|null,
     *   speed?: MessageCountTokensParams\Speed|value-of<MessageCountTokensParams\Speed>|null,
     *   system?: SystemShape,
     *   thinking?: BetaThinkingConfigParamShape,
     *   toolChoice?: BetaToolChoiceShape,
     *   tools?: list<ToolShape>,
     *   betas?: list<string|AnthropicBeta|value-of<AnthropicBeta>>,
     *   userProfileID?: string,
     *   workspaceID?: string,
     * }|MessageCountTokensParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaMessageTokensCount>
     *
     * @throws APIException
     */
    public function countTokens(
        array|MessageCountTokensParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse {
        [$parsed, $options] = MessageCountTokensParams::parseRequest(
            $params,
            $requestOptions,
        );
        $header_params = [
            'betas' => 'anthropic-beta',
            'userProfileID' => 'anthropic-user-profile-id',
            'workspaceID' => 'anthropic-workspace-id',
        ];

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'post',
            path: 'v1/messages/count_tokens?beta=true',
            headers: Util::array_transform_keys(
                array_intersect_key($parsed, array_flip(array_keys($header_params))),
                $header_params,
            ),
            body: (object) array_diff_key(
                $parsed,
                array_flip(array_keys($header_params))
            ),
            options: $options,
            convert: BetaMessageTokensCount::class,
        );
    }
}
