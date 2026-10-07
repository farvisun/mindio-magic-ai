<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Organization;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\RBACGroups\RBACGroup;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\RBACGroups\RBACGroupCreateParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\RBACGroups\RBACGroupDeleteResponse;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\RBACGroups\RBACGroupListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\RBACGroups\RBACGroupUpdateParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface RBACGroupsRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|RBACGroupCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<RBACGroup>
     *
     * @throws APIException
     */
    public function create(
        array|RBACGroupCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $rbacGroupID ID of the RBAC Group
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<RBACGroup>
     *
     * @throws APIException
     */
    public function retrieve(
        string $rbacGroupID,
        RequestOptions|array|null $requestOptions = null
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $rbacGroupID ID of the RBAC Group
     * @param array<string,mixed>|RBACGroupUpdateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<RBACGroup>
     *
     * @throws APIException
     */
    public function update(
        string $rbacGroupID,
        array|RBACGroupUpdateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|RBACGroupListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<RBACGroup>>
     *
     * @throws APIException
     */
    public function list(
        array|RBACGroupListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $rbacGroupID ID of the RBAC Group
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<RBACGroupDeleteResponse>
     *
     * @throws APIException
     */
    public function delete(
        string $rbacGroupID,
        RequestOptions|array|null $requestOptions = null
    ): BaseResponse;
}
