<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta;

use MindioMagicAI\Vendor\Anthropic\Beta\Environments\BetaEnvironment;
use MindioMagicAI\Vendor\Anthropic\Beta\Environments\BetaEnvironmentDeleteResponse;
use MindioMagicAI\Vendor\Anthropic\Beta\Environments\EnvironmentArchiveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Environments\EnvironmentCreateParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Environments\EnvironmentDeleteParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Environments\EnvironmentListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Environments\EnvironmentRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Environments\EnvironmentUpdateParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface EnvironmentsRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|EnvironmentCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaEnvironment>
     *
     * @throws APIException
     */
    public function create(
        array|EnvironmentCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|EnvironmentRetrieveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaEnvironment>
     *
     * @throws APIException
     */
    public function retrieve(
        string $environmentID,
        array|EnvironmentRetrieveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $environmentID Path param
     * @param array<string,mixed>|EnvironmentUpdateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaEnvironment>
     *
     * @throws APIException
     */
    public function update(
        string $environmentID,
        array|EnvironmentUpdateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|EnvironmentListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<BetaEnvironment>>
     *
     * @throws APIException
     */
    public function list(
        array|EnvironmentListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|EnvironmentDeleteParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaEnvironmentDeleteResponse>
     *
     * @throws APIException
     */
    public function delete(
        string $environmentID,
        array|EnvironmentDeleteParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|EnvironmentArchiveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaEnvironment>
     *
     * @throws APIException
     */
    public function archive(
        string $environmentID,
        array|EnvironmentArchiveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
