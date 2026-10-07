<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta;

use MindioMagicAI\Vendor\Anthropic\Beta\DeploymentRuns\BetaManagedAgentsDeploymentRun;
use MindioMagicAI\Vendor\Anthropic\Beta\Deployments\BetaManagedAgentsDeployment;
use MindioMagicAI\Vendor\Anthropic\Beta\Deployments\DeploymentArchiveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Deployments\DeploymentCreateParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Deployments\DeploymentListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Deployments\DeploymentPauseParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Deployments\DeploymentRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Deployments\DeploymentRunParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Deployments\DeploymentUnpauseParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Deployments\DeploymentUpdateParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface DeploymentsRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|DeploymentCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsDeployment>
     *
     * @throws APIException
     */
    public function create(
        array|DeploymentCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $deploymentID unique identifier of the deployment
     * @param array<string,mixed>|DeploymentRetrieveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsDeployment>
     *
     * @throws APIException
     */
    public function retrieve(
        string $deploymentID,
        array|DeploymentRetrieveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $deploymentID path param: Unique identifier of the deployment to update
     * @param array<string,mixed>|DeploymentUpdateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsDeployment>
     *
     * @throws APIException
     */
    public function update(
        string $deploymentID,
        array|DeploymentUpdateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|DeploymentListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<BetaManagedAgentsDeployment>>
     *
     * @throws APIException
     */
    public function list(
        array|DeploymentListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $deploymentID unique identifier of the deployment to archive
     * @param array<string,mixed>|DeploymentArchiveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsDeployment>
     *
     * @throws APIException
     */
    public function archive(
        string $deploymentID,
        array|DeploymentArchiveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $deploymentID unique identifier of the deployment to pause
     * @param array<string,mixed>|DeploymentPauseParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsDeployment>
     *
     * @throws APIException
     */
    public function pause(
        string $deploymentID,
        array|DeploymentPauseParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $deploymentID unique identifier of the deployment to run
     * @param array<string,mixed>|DeploymentRunParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsDeploymentRun>
     *
     * @throws APIException
     */
    public function run(
        string $deploymentID,
        array|DeploymentRunParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $deploymentID unique identifier of the deployment to unpause
     * @param array<string,mixed>|DeploymentUnpauseParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsDeployment>
     *
     * @throws APIException
     */
    public function unpause(
        string $deploymentID,
        array|DeploymentUnpauseParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
