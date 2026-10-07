<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts;

use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\Models\ModelInfo;
use MindioMagicAI\Vendor\Anthropic\Models\ModelListParams;
use MindioMagicAI\Vendor\Anthropic\Models\ModelRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Page;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface ModelsRawContract
{
    /**
     * @api
     *
     * @param string $modelID model identifier or alias
     * @param array<string,mixed>|ModelRetrieveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ModelInfo>
     *
     * @throws APIException
     */
    public function retrieve(
        string $modelID,
        array|ModelRetrieveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|ModelListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<Page<ModelInfo>>
     *
     * @throws APIException
     */
    public function list(
        array|ModelListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
