<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Organization\SpendLimits;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\SpendLimits\IncreaseRequests\BetaSpendLimitIncreaseRequest;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\SpendLimits\IncreaseRequests\IncreaseRequestApproveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\SpendLimits\IncreaseRequests\IncreaseRequestApproveResponse;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\SpendLimits\IncreaseRequests\IncreaseRequestDenyParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\SpendLimits\IncreaseRequests\IncreaseRequestListParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface IncreaseRequestsRawContract
{
    /**
     * @api
     *
     * @param string $spendLimitIncreaseRequestID ID of the spend limit increase request
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaSpendLimitIncreaseRequest>
     *
     * @throws APIException
     */
    public function retrieve(
        string $spendLimitIncreaseRequestID,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|IncreaseRequestListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<BetaSpendLimitIncreaseRequest>>
     *
     * @throws APIException
     */
    public function list(
        array|IncreaseRequestListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $spendLimitIncreaseRequestID ID of the spend limit increase request
     * @param array<string,mixed>|IncreaseRequestApproveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<IncreaseRequestApproveResponse>
     *
     * @throws APIException
     */
    public function approve(
        string $spendLimitIncreaseRequestID,
        array|IncreaseRequestApproveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $spendLimitIncreaseRequestID ID of the spend limit increase request
     * @param array<string,mixed>|IncreaseRequestDenyParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaSpendLimitIncreaseRequest>
     *
     * @throws APIException
     */
    public function deny(
        string $spendLimitIncreaseRequestID,
        array|IncreaseRequestDenyParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
