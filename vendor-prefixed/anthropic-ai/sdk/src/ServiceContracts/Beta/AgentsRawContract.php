<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta;

use MindioMagicAI\Vendor\Anthropic\Beta\Agents\AgentArchiveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\AgentCreateParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\AgentListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\AgentRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\AgentUpdateParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsAgent;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface AgentsRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|AgentCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsAgent>
     *
     * @throws APIException
     */
    public function create(
        array|AgentCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $agentID path param: Unique identifier of the agent to retrieve
     * @param array<string,mixed>|AgentRetrieveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsAgent>
     *
     * @throws APIException
     */
    public function retrieve(
        string $agentID,
        array|AgentRetrieveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $agentID path param: Unique identifier of the agent to update
     * @param array<string,mixed>|AgentUpdateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsAgent>
     *
     * @throws APIException
     */
    public function update(
        string $agentID,
        array|AgentUpdateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|AgentListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<BetaManagedAgentsAgent>>
     *
     * @throws APIException
     */
    public function list(
        array|AgentListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $agentID unique identifier of the agent to archive
     * @param array<string,mixed>|AgentArchiveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsAgent>
     *
     * @throws APIException
     */
    public function archive(
        string $agentID,
        array|AgentArchiveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
