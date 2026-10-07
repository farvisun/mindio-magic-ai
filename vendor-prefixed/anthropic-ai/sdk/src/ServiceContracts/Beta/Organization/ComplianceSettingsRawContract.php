<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Organization;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\ComplianceSettings\ComplianceSettings;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\ComplianceSettings\ComplianceSettingUpdateParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface ComplianceSettingsRawContract
{
    /**
     * @api
     *
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ComplianceSettings>
     *
     * @throws APIException
     */
    public function retrieve(
        RequestOptions|array|null $requestOptions = null
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|ComplianceSettingUpdateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ComplianceSettings>
     *
     * @throws APIException
     */
    public function update(
        array|ComplianceSettingUpdateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
