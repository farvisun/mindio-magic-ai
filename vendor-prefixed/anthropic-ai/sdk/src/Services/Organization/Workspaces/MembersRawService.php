<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Services\Organization\Workspaces;

use MindioMagicAI\Vendor\Anthropic\Client;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\Core\Util;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\Members\MemberAddParams;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\Members\MemberListParams;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\Members\MemberRemoveParams;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\Members\MemberRemoveResponse;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\Members\MemberRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\Members\MemberUpdateParams;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\NoBillingWorkspaceRole;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\WorkspaceMember;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\WorkspaceRole;
use MindioMagicAI\Vendor\Anthropic\Page;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;
use MindioMagicAI\Vendor\Anthropic\ServiceContracts\Organization\Workspaces\MembersRawContract;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
final class MembersRawService implements MembersRawContract
{
    // @phpstan-ignore-next-line
    /**
     * @internal
     */
    public function __construct(private Client $client) {}

    /**
     * @api
     *
     * Get Workspace Member
     *
     * @param string $userID ID of the User
     * @param array{workspaceID: string}|MemberRetrieveParams $params
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
    ): BaseResponse {
        [$parsed, $options] = MemberRetrieveParams::parseRequest(
            $params,
            $requestOptions,
        );
        $workspaceID = $parsed['workspaceID'];
        unset($parsed['workspaceID']);

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'get',
            path: [
                'v1/organizations/workspaces/%1$s/members/%2$s', $workspaceID, $userID,
            ],
            options: $options,
            convert: WorkspaceMember::class,
        );
    }

    /**
     * @api
     *
     * Update Workspace Member
     *
     * @param string $userID path param: ID of the User
     * @param array{
     *   workspaceID: string, workspaceRole: value-of<WorkspaceRole>
     * }|MemberUpdateParams $params
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
    ): BaseResponse {
        [$parsed, $options] = MemberUpdateParams::parseRequest(
            $params,
            $requestOptions,
        );
        $workspaceID = $parsed['workspaceID'];
        unset($parsed['workspaceID']);

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'post',
            path: [
                'v1/organizations/workspaces/%1$s/members/%2$s', $workspaceID, $userID,
            ],
            body: (object) array_diff_key($parsed, array_flip(['workspaceID'])),
            options: $options,
            convert: WorkspaceMember::class,
        );
    }

    /**
     * @api
     *
     * List Workspace Members
     *
     * @param string $workspaceID ID of the Workspace
     * @param array{
     *   afterID?: string, beforeID?: string, limit?: int
     * }|MemberListParams $params
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
    ): BaseResponse {
        [$parsed, $options] = MemberListParams::parseRequest(
            $params,
            $requestOptions,
        );

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'get',
            path: ['v1/organizations/workspaces/%1$s/members', $workspaceID],
            query: Util::array_transform_keys(
                $parsed,
                ['afterID' => 'after_id', 'beforeID' => 'before_id']
            ),
            options: $options,
            convert: WorkspaceMember::class,
            page: Page::class,
        );
    }

    /**
     * @api
     *
     * Create Workspace Member
     *
     * @param string $workspaceID ID of the Workspace
     * @param array{
     *   userID: string, workspaceRole: value-of<NoBillingWorkspaceRole>
     * }|MemberAddParams $params
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
    ): BaseResponse {
        [$parsed, $options] = MemberAddParams::parseRequest(
            $params,
            $requestOptions,
        );

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'post',
            path: ['v1/organizations/workspaces/%1$s/members', $workspaceID],
            body: (object) $parsed,
            options: $options,
            convert: WorkspaceMember::class,
        );
    }

    /**
     * @api
     *
     * Delete Workspace Member
     *
     * @param string $userID ID of the User
     * @param array{workspaceID: string}|MemberRemoveParams $params
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
    ): BaseResponse {
        [$parsed, $options] = MemberRemoveParams::parseRequest(
            $params,
            $requestOptions,
        );
        $workspaceID = $parsed['workspaceID'];
        unset($parsed['workspaceID']);

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'delete',
            path: [
                'v1/organizations/workspaces/%1$s/members/%2$s', $workspaceID, $userID,
            ],
            options: $options,
            convert: MemberRemoveResponse::class,
        );
    }
}
