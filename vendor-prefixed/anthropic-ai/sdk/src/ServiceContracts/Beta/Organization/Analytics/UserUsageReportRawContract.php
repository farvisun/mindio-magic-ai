<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Organization\Analytics;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Analytics\AnalyticsUsageUsersItem;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Analytics\UserUsageReport\UserUsageReportListParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface UserUsageReportRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|UserUsageReportListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<AnalyticsUsageUsersItem>>
     *
     * @throws APIException
     */
    public function list(
        array|UserUsageReportListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
