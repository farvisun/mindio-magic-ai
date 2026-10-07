<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Organization\SpendLimits;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\SpendLimits\Effective\EffectiveListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\SpendLimits\SpendSummary;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface EffectiveRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|EffectiveListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<SpendSummary>>
     *
     * @throws APIException
     */
    public function list(
        array|EffectiveListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
