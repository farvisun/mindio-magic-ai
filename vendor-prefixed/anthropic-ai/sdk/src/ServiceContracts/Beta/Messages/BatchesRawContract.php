<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Messages;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\Batches\BatchCancelParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\Batches\BatchCreateParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\Batches\BatchDeleteParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\Batches\BatchListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\Batches\BatchResultsParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\Batches\BatchRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\Batches\DeletedMessageBatch;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\Batches\MessageBatch;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\Batches\MessageBatchIndividualResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseStream;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\Page;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface BatchesRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|BatchCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<MessageBatch>
     *
     * @throws APIException
     */
    public function create(
        array|BatchCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $messageBatchID ID of the Message Batch
     * @param array<string,mixed>|BatchRetrieveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<MessageBatch>
     *
     * @throws APIException
     */
    public function retrieve(
        string $messageBatchID,
        array|BatchRetrieveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|BatchListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<Page<MessageBatch>>
     *
     * @throws APIException
     */
    public function list(
        array|BatchListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $messageBatchID ID of the Message Batch
     * @param array<string,mixed>|BatchDeleteParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<DeletedMessageBatch>
     *
     * @throws APIException
     */
    public function delete(
        string $messageBatchID,
        array|BatchDeleteParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $messageBatchID ID of the Message Batch
     * @param array<string,mixed>|BatchCancelParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<MessageBatch>
     *
     * @throws APIException
     */
    public function cancel(
        string $messageBatchID,
        array|BatchCancelParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $messageBatchID ID of the Message Batch
     * @param array<string,mixed>|BatchResultsParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BaseStream<MessageBatchIndividualResponse>>
     *
     * @throws APIException
     */
    public function resultsStream(
        string $messageBatchID,
        array|BatchResultsParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
