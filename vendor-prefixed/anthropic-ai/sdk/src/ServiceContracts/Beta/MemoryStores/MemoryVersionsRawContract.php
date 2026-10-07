<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\MemoryStores;

use MindioMagicAI\Vendor\Anthropic\Beta\MemoryStores\MemoryVersions\ManagedAgentsMemoryVersion;
use MindioMagicAI\Vendor\Anthropic\Beta\MemoryStores\MemoryVersions\MemoryVersionListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\MemoryStores\MemoryVersions\MemoryVersionRedactParams;
use MindioMagicAI\Vendor\Anthropic\Beta\MemoryStores\MemoryVersions\MemoryVersionRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface MemoryVersionsRawContract
{
    /**
     * @api
     *
     * @param string $memoryVersionID Path param: The ID of the memory version to retrieve (`memver_...`).
     * @param array<string,mixed>|MemoryVersionRetrieveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ManagedAgentsMemoryVersion>
     *
     * @throws APIException
     */
    public function retrieve(
        string $memoryVersionID,
        array|MemoryVersionRetrieveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $memoryStoreID Path param: The ID of the memory store whose version history to list (`memstore_...`).
     * @param array<string,mixed>|MemoryVersionListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<ManagedAgentsMemoryVersion>>
     *
     * @throws APIException
     */
    public function list(
        string $memoryStoreID,
        array|MemoryVersionListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $memoryVersionID Path param: The ID of the memory version to redact (`memver_...`).
     * @param array<string,mixed>|MemoryVersionRedactParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ManagedAgentsMemoryVersion>
     *
     * @throws APIException
     */
    public function redact(
        string $memoryVersionID,
        array|MemoryVersionRedactParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
