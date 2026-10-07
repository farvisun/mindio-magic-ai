<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Organization\Federation;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Federation\Rules\BetaFederationRule;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Federation\Rules\RuleArchiveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Federation\Rules\RuleCreateParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Federation\Rules\RuleListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Federation\Rules\RuleRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Federation\Rules\RuleUpdateParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
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
     * @return BaseResponse<BetaFederationRule>
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
     * @param array<string,mixed>|RuleRetrieveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaFederationRule>
     *
     * @throws APIException
     */
    public function retrieve(
        string $federationRuleID,
        array|RuleRetrieveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $federationRuleID path param: ID of the federation rule to update
     * @param array<string,mixed>|RuleUpdateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaFederationRule>
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
     * @return BaseResponse<PageCursor<BetaFederationRule>>
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
     * @param array<string,mixed>|RuleArchiveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaFederationRule>
     *
     * @throws APIException
     */
    public function archive(
        string $federationRuleID,
        array|RuleArchiveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
