<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Organization\Analytics;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Analytics\AnalyticsSingleDayActivitySummary;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Analytics\Summaries\SummaryListParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface SummariesRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|SummaryListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<AnalyticsSingleDayActivitySummary>>
     *
     * @throws APIException
     */
    public function list(
        array|SummaryListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
