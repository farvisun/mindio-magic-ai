<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaMessage;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaMessageTokensCount;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRawContentBlockDeltaEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRawContentBlockStartEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRawContentBlockStopEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRawMessageDeltaEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRawMessageStartEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRawMessageStopEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\MessageCountTokensParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\MessageCreateParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseStream;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
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
     * @return BaseResponse<BetaMessage>
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
     * @return BaseResponse<BaseStream<BetaRawMessageStartEvent|BetaRawMessageDeltaEvent|BetaRawMessageStopEvent|BetaRawContentBlockStartEvent|BetaRawContentBlockDeltaEvent|BetaRawContentBlockStopEvent,>,>
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
     * @return BaseResponse<BetaMessageTokensCount>
     *
     * @throws APIException
     */
    public function countTokens(
        array|MessageCountTokensParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
