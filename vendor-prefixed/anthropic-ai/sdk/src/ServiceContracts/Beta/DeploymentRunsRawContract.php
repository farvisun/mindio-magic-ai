<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta;

use MindioMagicAI\Vendor\Anthropic\Beta\DeploymentRuns\BetaManagedAgentsDeploymentRun;
use MindioMagicAI\Vendor\Anthropic\Beta\DeploymentRuns\DeploymentRunListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\DeploymentRuns\DeploymentRunRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface DeploymentRunsRawContract
{
    /**
     * @api
     *
     * @param string $deploymentRunID unique identifier of the deployment run
     * @param array<string,mixed>|DeploymentRunRetrieveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaManagedAgentsDeploymentRun>
     *
     * @throws APIException
     */
    public function retrieve(
        string $deploymentRunID,
        array|DeploymentRunRetrieveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|DeploymentRunListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<BetaManagedAgentsDeploymentRun>>
     *
     * @throws APIException
     */
    public function list(
        array|DeploymentRunListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
