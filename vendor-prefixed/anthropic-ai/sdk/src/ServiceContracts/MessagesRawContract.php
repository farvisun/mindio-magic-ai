<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts;

use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseStream;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\Messages\Message;
use MindioMagicAI\Vendor\Anthropic\Messages\MessageCountTokensParams;
use MindioMagicAI\Vendor\Anthropic\Messages\MessageCreateParams;
use MindioMagicAI\Vendor\Anthropic\Messages\MessageTokensCount;
use MindioMagicAI\Vendor\Anthropic\Messages\RawContentBlockDeltaEvent;
use MindioMagicAI\Vendor\Anthropic\Messages\RawContentBlockStartEvent;
use MindioMagicAI\Vendor\Anthropic\Messages\RawContentBlockStopEvent;
use MindioMagicAI\Vendor\Anthropic\Messages\RawMessageDeltaEvent;
use MindioMagicAI\Vendor\Anthropic\Messages\RawMessageStartEvent;
use MindioMagicAI\Vendor\Anthropic\Messages\RawMessageStopEvent;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface MessagesRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|MessageCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<Message>
     *
     * @throws APIException
     */
    public function create(
        array|MessageCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|MessageCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BaseStream<RawMessageStartEvent|RawMessageDeltaEvent|RawMessageStopEvent|RawContentBlockStartEvent|RawContentBlockDeltaEvent|RawContentBlockStopEvent,>,>
     *
     * @throws APIException
     */
    public function createStream(
        array|MessageCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|MessageCountTokensParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<MessageTokensCount>
     *
     * @throws APIException
     */
    public function countTokens(
        array|MessageCountTokensParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
