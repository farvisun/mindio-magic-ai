<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Services\Organization;

use MindioMagicAI\Vendor\Anthropic\Client;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\Core\Util;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\DataResidencyCreateConfig;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\DataResidencyUpdateConfig;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\Workspace;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\WorkspaceCreateParams;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\WorkspaceListParams;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\WorkspaceUpdateParams;
use MindioMagicAI\Vendor\Anthropic\Page;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;
use MindioMagicAI\Vendor\Anthropic\ServiceContracts\Organization\WorkspacesRawContract;

/**
 * @phpstan-import-type DataResidencyCreateConfigShape from \Anthropic\Organization\Workspaces\DataResidencyCreateConfig
 * @phpstan-import-type DataResidencyUpdateConfigShape from \Anthropic\Organization\Workspaces\DataResidencyUpdateConfig
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
final class WorkspacesRawService implements WorkspacesRawContract
{
    // @phpstan-ignore-next-line
    /**
     * @internal
     */
    public function __construct(private Client $client) {}

    /**
     * @api
     *
     * Create Workspace
     *
     * @param array{
     *   name: string,
     *   dataResidency?: DataResidencyCreateConfig|DataResidencyCreateConfigShape|null,
     *   displayColor?: string|null,
     *   externalKeyID?: string|null,
     *   tags?: array<string,string>|null,
     * }|WorkspaceCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<Workspace>
     *
     * @throws APIException
     */
    public function create(
        array|WorkspaceCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse {
        [$parsed, $options] = WorkspaceCreateParams::parseRequest(
            $params,
            $requestOptions,
        );

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'post',
            path: 'v1/organizations/workspaces',
            body: (object) $parsed,
            options: $options,
            convert: Workspace::class,
        );
    }

    /**
     * @api
     *
     * Get Workspace
     *
     * @param string $workspaceID ID of the Workspace
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<Workspace>
     *
     * @throws APIException
     */
    public function retrieve(
        string $workspaceID,
        RequestOptions|array|null $requestOptions = null
    ): BaseResponse {
        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'get',
            path: ['v1/organizations/workspaces/%1$s', $workspaceID],
            options: $requestOptions,
            convert: Workspace::class,
        );
    }

    /**
     * @api
     *
     * Update Workspace
     *
     * @param array{
     *   dataResidency?: DataResidencyUpdateConfig|DataResidencyUpdateConfigShape|null,
     *   displayColor?: string,
     *   externalKeyID?: string,
     *   name?: string,
     *   tags?: array<string,string|null>|null,
     * }|WorkspaceUpdateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<Workspace>
     *
     * @throws APIException
     */
    public function update(
        string $workspaceID,
        array|WorkspaceUpdateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse {
        [$parsed, $options] = WorkspaceUpdateParams::parseRequest(
            $params,
            $requestOptions,
        );

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'post',
            path: ['v1/organizations/workspaces/%1$s', $workspaceID],
            body: (object) $parsed,
            options: $options,
            convert: Workspace::class,
        );
    }

    /**
     * @api
     *
     * List Workspaces
     *
     * @param array{
     *   afterID?: string, beforeID?: string, includeArchived?: bool, limit?: int
     * }|WorkspaceListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<Page<Workspace>>
     *
     * @throws APIException
     */
    public function list(
        array|WorkspaceListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse {
        [$parsed, $options] = WorkspaceListParams::parseRequest(
            $params,
            $requestOptions,
        );

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'get',
            path: 'v1/organizations/workspaces',
            query: Util::array_transform_keys(
                $parsed,
                [
                    'afterID' => 'after_id',
                    'beforeID' => 'before_id',
                    'includeArchived' => 'include_archived',
                ],
            ),
            options: $options,
            convert: Workspace::class,
            page: Page::class,
        );
    }

    /**
     * @api
     *
     * Archive Workspace
     *
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<Workspace>
     *
     * @throws APIException
     */
    public function archive(
        string $workspaceID,
        RequestOptions|array|null $requestOptions = null
    ): BaseResponse {
        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'post',
            path: ['v1/organizations/workspaces/%1$s/archive', $workspaceID],
            options: $requestOptions,
            convert: Workspace::class,
        );
    }
}
