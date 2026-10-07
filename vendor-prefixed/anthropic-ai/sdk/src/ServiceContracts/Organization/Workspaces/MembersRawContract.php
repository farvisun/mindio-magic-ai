<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Organization\Workspaces;

use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\Members\MemberAddParams;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\Members\MemberListParams;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\Members\MemberRemoveParams;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\Members\MemberRemoveResponse;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\Members\MemberRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\Members\MemberUpdateParams;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\WorkspaceMember;
use MindioMagicAI\Vendor\Anthropic\Page;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface MembersRawContract
{
    /**
     * @api
     *
     * @param string $userID ID of the User
     * @param array<string,mixed>|MemberRetrieveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<WorkspaceMember>
     *
     * @throws APIException
     */
    public function retrieve(
        string $userID,
        array|MemberRetrieveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $userID path param: ID of the User
     * @param array<string,mixed>|MemberUpdateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<WorkspaceMember>
     *
     * @throws APIException
     */
    public function update(
        string $userID,
        array|MemberUpdateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $workspaceID ID of the Workspace
     * @param array<string,mixed>|MemberListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<Page<WorkspaceMember>>
     *
     * @throws APIException
     */
    public function list(
        string $workspaceID,
        array|MemberListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $workspaceID ID of the Workspace
     * @param array<string,mixed>|MemberAddParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<WorkspaceMember>
     *
     * @throws APIException
     */
    public function add(
        string $workspaceID,
        array|MemberAddParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $userID ID of the User
     * @param array<string,mixed>|MemberRemoveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<MemberRemoveResponse>
     *
     * @throws APIException
     */
    public function remove(
        string $userID,
        array|MemberRemoveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
