<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta;

use MindioMagicAI\Vendor\Anthropic\Beta\Dreams\BetaDream;
use MindioMagicAI\Vendor\Anthropic\Beta\Dreams\DreamArchiveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Dreams\DreamCancelParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Dreams\DreamCreateParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Dreams\DreamListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Dreams\DreamRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface DreamsRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|DreamCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaDream>
     *
     * @throws APIException
     */
    public function create(
        array|DreamCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $dreamID The ID of the dream to get (`drm_...`).
     * @param array<string,mixed>|DreamRetrieveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaDream>
     *
     * @throws APIException
     */
    public function retrieve(
        string $dreamID,
        array|DreamRetrieveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|DreamListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<BetaDream>>
     *
     * @throws APIException
     */
    public function list(
        array|DreamListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $dreamID The ID of the dream to archive (`drm_...`).
     * @param array<string,mixed>|DreamArchiveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaDream>
     *
     * @throws APIException
     */
    public function archive(
        string $dreamID,
        array|DreamArchiveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $dreamID The ID of the dream to cancel (`drm_...`).
     * @param array<string,mixed>|DreamCancelParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaDream>
     *
     * @throws APIException
     */
    public function cancel(
        string $dreamID,
        array|DreamCancelParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
