<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta;

use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsDeletedSession;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsSession;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\SessionArchiveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\SessionCreateParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\SessionDeleteParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\SessionListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\SessionRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\SessionUpdateParams;
use MindioMagicAI\Vendor\Anthropic\BidirectionalPageCursor;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface SessionsRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|SessionCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsSession>
     *
     * @throws APIException
     */
    public function create(
        array|SessionCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|SessionRetrieveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsSession>
     *
     * @throws APIException
     */
    public function retrieve(
        string $sessionID,
        array|SessionRetrieveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $sessionID Path param
     * @param array<string,mixed>|SessionUpdateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsSession>
     *
     * @throws APIException
     */
    public function update(
        string $sessionID,
        array|SessionUpdateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|SessionListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BidirectionalPageCursor<BetaManagedAgentsSession>>
     *
     * @throws APIException
     */
    public function list(
        array|SessionListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|SessionDeleteParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsDeletedSession>
     *
     * @throws APIException
     */
    public function delete(
        string $sessionID,
        array|SessionDeleteParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|SessionArchiveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsSession>
     *
     * @throws APIException
     */
    public function archive(
        string $sessionID,
        array|SessionArchiveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
