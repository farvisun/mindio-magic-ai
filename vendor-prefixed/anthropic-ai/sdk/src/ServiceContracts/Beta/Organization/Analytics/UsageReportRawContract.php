<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Organization\Analytics;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Analytics\AnalyticsUsageReportTimeBucket;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Analytics\UsageReport\UsageReportListParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface UsageReportRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|UsageReportListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<AnalyticsUsageReportTimeBucket>>
     *
     * @throws APIException
     */
    public function list(
        array|UsageReportListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
