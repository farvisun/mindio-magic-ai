<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Organization\Analytics;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Analytics\AnalyticsConnectorActivity;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Analytics\Connectors\ConnectorListParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface ConnectorsRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|ConnectorListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<AnalyticsConnectorActivity>>
     *
     * @throws APIException
     */
    public function list(
        array|ConnectorListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
