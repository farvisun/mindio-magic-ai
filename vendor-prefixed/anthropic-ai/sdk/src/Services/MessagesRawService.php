<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Services;

use MindioMagicAI\Vendor\Anthropic\Client;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseStream;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\Core\Util;
use MindioMagicAI\Vendor\Anthropic\Messages\CacheControlEphemeral;
use MindioMagicAI\Vendor\Anthropic\Messages\DiagnosticsParam;
use MindioMagicAI\Vendor\Anthropic\Messages\Message;
use MindioMagicAI\Vendor\Anthropic\Messages\MessageCountTokensParams;
use MindioMagicAI\Vendor\Anthropic\Messages\MessageCreateParams;
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
use MindioMagicAI\Vendor\Anthropic\Messages\RawMessageStreamEvent;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;
use MindioMagicAI\Vendor\Anthropic\ServiceContracts\MessagesRawContract;
use MindioMagicAI\Vendor\Anthropic\SSEStream;

/**
 * @phpstan-import-type SystemShape from \Anthropic\Messages\MessageCountTokensParams\System
 * @phpstan-import-type MessageCountTokensToolShape from \Anthropic\Messages\MessageCountTokensTool
 * @phpstan-import-type MessageParamShape from \Anthropic\Messages\MessageParam
 * @phpstan-import-type CacheControlEphemeralShape from \Anthropic\Messages\CacheControlEphemeral
 * @phpstan-import-type MessageCreateParamsContainerShape from \Anthropic\Messages\MessageCreateParamsContainer
 * @phpstan-import-type DiagnosticsParamShape from \Anthropic\Messages\DiagnosticsParam
 * @phpstan-import-type MetadataShape from \Anthropic\Messages\Metadata
 * @phpstan-import-type OutputConfigShape from \Anthropic\Messages\OutputConfig
 * @phpstan-import-type SystemShape from \Anthropic\Messages\MessageCreateParams\System as SystemShape1
 * @phpstan-import-type ThinkingConfigParamShape from \Anthropic\Messages\ThinkingConfigParam
 * @phpstan-import-type ToolChoiceShape from \Anthropic\Messages\ToolChoice
 * @phpstan-import-type ToolUnionShape from \Anthropic\Messages\ToolUnion
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
     *   messages: list<MessageParam|MessageParamShape>,
     *   model: string|Model|value-of<Model>,
     *   cacheControl?: CacheControlEphemeral|CacheControlEphemeralShape|null,
     *   container?: MessageCreateParamsContainerShape|null,
     *   diagnostics?: DiagnosticsParam|DiagnosticsParamShape|null,
     *   inferenceGeo?: string|null,
     *   metadata?: Metadata|MetadataShape,
     *   outputConfig?: OutputConfig|OutputConfigShape,
     *   serviceTier?: ServiceTier|value-of<ServiceTier>,
     *   stopSequences?: list<string>,
     *   system?: SystemShape1,
     *   temperature?: float,
     *   thinking?: ThinkingConfigParamShape,
     *   toolChoice?: ToolChoiceShape,
     *   tools?: list<ToolUnionShape>,
     *   topK?: int,
     *   topP?: float,
     *   userProfileID?: string,
     *   workspaceID?: string,
     * }|MessageCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<Message>
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
            'userProfileID' => 'anthropic-user-profile-id',
            'workspaceID' => 'anthropic-workspace-id',
        ];

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'post',
            path: 'v1/messages',
            headers: Util::array_transform_keys(
                array_intersect_key($parsed, array_flip(array_keys($header_params))),
                $header_params,
            ),
            body: (object) array_diff_key(
                $parsed,
                array_flip(array_keys($header_params))
            ),
            options: $options,
            convert: Message::class,
        );
    }

    /**
     * @api
     *
     * @param array{
     *   maxTokens: int,
     *   messages: list<MessageParam|MessageParamShape>,
     *   model: string|Model|value-of<Model>,
     *   cacheControl?: CacheControlEphemeral|CacheControlEphemeralShape|null,
     *   container?: MessageCreateParamsContainerShape|null,
     *   diagnostics?: DiagnosticsParam|DiagnosticsParamShape|null,
     *   inferenceGeo?: string|null,
     *   metadata?: Metadata|MetadataShape,
     *   outputConfig?: OutputConfig|OutputConfigShape,
     *   serviceTier?: ServiceTier|value-of<ServiceTier>,
     *   stopSequences?: list<string>,
     *   system?: SystemShape1,
     *   temperature?: float,
     *   thinking?: ThinkingConfigParamShape,
     *   toolChoice?: ToolChoiceShape,
     *   tools?: list<ToolUnionShape>,
     *   topK?: int,
     *   topP?: float,
     *   userProfileID?: string,
     *   workspaceID?: string,
     * }|MessageCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BaseStream<RawMessageStartEvent|RawMessageDeltaEvent|RawMessageStopEvent|RawContentBlockStartEvent|RawContentBlockDeltaEvent|RawContentBlockStopEvent,>,>
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
            'userProfileID' => 'anthropic-user-profile-id',
            'workspaceID' => 'anthropic-workspace-id',
        ];

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'post',
            path: 'v1/messages',
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
            convert: RawMessageStreamEvent::class,
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
     *   messages: list<MessageParam|MessageParamShape>,
     *   model: string|Model|value-of<Model>,
     *   cacheControl?: CacheControlEphemeral|CacheControlEphemeralShape|null,
     *   outputConfig?: OutputConfig|OutputConfigShape,
     *   system?: SystemShape,
     *   thinking?: ThinkingConfigParamShape,
     *   toolChoice?: ToolChoiceShape,
     *   tools?: list<MessageCountTokensToolShape>,
     *   userProfileID?: string,
     *   workspaceID?: string,
     * }|MessageCountTokensParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<MessageTokensCount>
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
            'userProfileID' => 'anthropic-user-profile-id',
            'workspaceID' => 'anthropic-workspace-id',
        ];

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'post',
            path: 'v1/messages/count_tokens',
            headers: Util::array_transform_keys(
                array_intersect_key($parsed, array_flip(array_keys($header_params))),
                $header_params,
            ),
            body: (object) array_diff_key(
                $parsed,
                array_flip(array_keys($header_params))
            ),
            options: $options,
            convert: MessageTokensCount::class,
        );
    }
}
