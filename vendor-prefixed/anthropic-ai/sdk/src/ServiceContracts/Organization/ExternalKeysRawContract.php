<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Organization;

use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\Organization\ExternalKeys\ExternalKey;
use MindioMagicAI\Vendor\Anthropic\Organization\ExternalKeys\ExternalKeyCreateParams;
use MindioMagicAI\Vendor\Anthropic\Organization\ExternalKeys\ExternalKeyDeleteResponse;
use MindioMagicAI\Vendor\Anthropic\Organization\ExternalKeys\ExternalKeyListParams;
use MindioMagicAI\Vendor\Anthropic\Organization\ExternalKeys\ExternalKeyUpdateParams;
use MindioMagicAI\Vendor\Anthropic\Organization\ExternalKeys\ExternalKeyValidateResponse;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface ExternalKeysRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|ExternalKeyCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ExternalKey>
     *
     * @throws APIException
     */
    public function create(
        array|ExternalKeyCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $externalKeyID ID of the External Key
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ExternalKey>
     *
     * @throws APIException
     */
    public function retrieve(
        string $externalKeyID,
        RequestOptions|array|null $requestOptions = null
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $externalKeyID ID of the External Key
     * @param array<string,mixed>|ExternalKeyUpdateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ExternalKey>
     *
     * @throws APIException
     */
    public function update(
        string $externalKeyID,
        array|ExternalKeyUpdateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|ExternalKeyListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<ExternalKey>>
     *
     * @throws APIException
     */
    public function list(
        array|ExternalKeyListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $externalKeyID ID of the External Key
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ExternalKeyDeleteResponse>
     *
     * @throws APIException
     */
    public function delete(
        string $externalKeyID,
        RequestOptions|array|null $requestOptions = null
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $externalKeyID ID of the External Key
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ExternalKeyValidateResponse>
     *
     * @throws APIException
     */
    public function validate(
        string $externalKeyID,
        RequestOptions|array|null $requestOptions = null
    ): BaseResponse;
}
