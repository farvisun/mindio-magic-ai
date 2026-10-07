<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Sessions;

use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Threads\ManagedAgentsSessionThread;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Threads\ThreadArchiveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Threads\ThreadListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Threads\ThreadRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface ThreadsRawContract
{
    /**
     * @api
     *
     * @param string $threadID Path param
     * @param array<string,mixed>|ThreadRetrieveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ManagedAgentsSessionThread>
     *
     * @throws APIException
     */
    public function retrieve(
        string $threadID,
        array|ThreadRetrieveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $sessionID Path param
     * @param array<string,mixed>|ThreadListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<ManagedAgentsSessionThread>>
     *
     * @throws APIException
     */
    public function list(
        string $sessionID,
        array|ThreadListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $threadID Path param
     * @param array<string,mixed>|ThreadArchiveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ManagedAgentsSessionThread>
     *
     * @throws APIException
     */
    public function archive(
        string $threadID,
        array|ThreadArchiveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
