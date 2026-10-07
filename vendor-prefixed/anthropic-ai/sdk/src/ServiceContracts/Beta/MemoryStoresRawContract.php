<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta;

use MindioMagicAI\Vendor\Anthropic\Beta\MemoryStores\BetaManagedAgentsDeletedMemoryStore;
use MindioMagicAI\Vendor\Anthropic\Beta\MemoryStores\BetaManagedAgentsMemoryStore;
use MindioMagicAI\Vendor\Anthropic\Beta\MemoryStores\MemoryStoreArchiveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\MemoryStores\MemoryStoreCreateParams;
use MindioMagicAI\Vendor\Anthropic\Beta\MemoryStores\MemoryStoreDeleteParams;
use MindioMagicAI\Vendor\Anthropic\Beta\MemoryStores\MemoryStoreListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\MemoryStores\MemoryStoreRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\MemoryStores\MemoryStoreUpdateParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface MemoryStoresRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|MemoryStoreCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsMemoryStore>
     *
     * @throws APIException
     */
    public function create(
        array|MemoryStoreCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $memoryStoreID ID of the memory store to retrieve (a `memstore_...` identifier). Required. Enumerate IDs via `GET /v1/memory_stores`.
     * @param array<string,mixed>|MemoryStoreRetrieveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsMemoryStore>
     *
     * @throws APIException
     */
    public function retrieve(
        string $memoryStoreID,
        array|MemoryStoreRetrieveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $memoryStoreID Path param: ID of the memory store to update (a `memstore_...` identifier). Required. Enumerate IDs via `GET /v1/memory_stores`. Updating an archived store returns 400.
     * @param array<string,mixed>|MemoryStoreUpdateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsMemoryStore>
     *
     * @throws APIException
     */
    public function update(
        string $memoryStoreID,
        array|MemoryStoreUpdateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|MemoryStoreListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<BetaManagedAgentsMemoryStore>>
     *
     * @throws APIException
     */
    public function list(
        array|MemoryStoreListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $memoryStoreID ID of the memory store to permanently delete (a `memstore_...` identifier). Required. Deletion cascades to all memories and memory versions in the store and cannot be undone.
     * @param array<string,mixed>|MemoryStoreDeleteParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsDeletedMemoryStore>
     *
     * @throws APIException
     */
    public function delete(
        string $memoryStoreID,
        array|MemoryStoreDeleteParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $memoryStoreID ID of the memory store to archive (a `memstore_...` identifier). Required. Archiving is one-way and idempotent; archived stores cannot be unarchived. Enumerate IDs via `GET /v1/memory_stores`.
     * @param array<string,mixed>|MemoryStoreArchiveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsMemoryStore>
     *
     * @throws APIException
     */
    public function archive(
        string $memoryStoreID,
        array|MemoryStoreArchiveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
