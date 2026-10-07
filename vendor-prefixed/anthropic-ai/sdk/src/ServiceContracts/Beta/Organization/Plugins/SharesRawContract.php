<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Organization\Plugins;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Plugins\Shares\BetaPluginShare;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Plugins\Shares\ShareListParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface SharesRawContract
{
    /**
     * @api
     *
     * @param string $pluginID path param: ID of the Plugin (prefixed `plugin_`)
     * @param array<string,mixed>|ShareListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<BetaPluginShare>>
     *
     * @throws APIException
     */
    public function list(
        string $pluginID,
        array|ShareListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
