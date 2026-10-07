<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Organization\Federation;

use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\Organization\Federation\Rules\FederationRule;
use MindioMagicAI\Vendor\Anthropic\Organization\Federation\Rules\RuleCreateParams;
use MindioMagicAI\Vendor\Anthropic\Organization\Federation\Rules\RuleListParams;
use MindioMagicAI\Vendor\Anthropic\Organization\Federation\Rules\RuleUpdateParams;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface RulesRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|RuleCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<FederationRule>
     *
     * @throws APIException
     */
    public function create(
        array|RuleCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $federationRuleID ID of the federation rule
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<FederationRule>
     *
     * @throws APIException
     */
    public function retrieve(
        string $federationRuleID,
        RequestOptions|array|null $requestOptions = null
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $federationRuleID ID of the federation rule to update
     * @param array<string,mixed>|RuleUpdateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<FederationRule>
     *
     * @throws APIException
     */
    public function update(
        string $federationRuleID,
        array|RuleUpdateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|RuleListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<FederationRule>>
     *
     * @throws APIException
     */
    public function list(
        array|RuleListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $federationRuleID ID of the federation rule to archive
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<FederationRule>
     *
     * @throws APIException
     */
    public function archive(
        string $federationRuleID,
        RequestOptions|array|null $requestOptions = null
    ): BaseResponse;
}
