<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Organization\Analytics;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Analytics\AnalyticsCostReportTimeBucket;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Analytics\CostReport\CostReportListParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface CostReportRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|CostReportListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<AnalyticsCostReportTimeBucket>>
     *
     * @throws APIException
     */
    public function list(
        array|CostReportListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
