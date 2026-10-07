<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Services\Beta;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\BetaOrganization;
use MindioMagicAI\Vendor\Anthropic\Client;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;
use MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\OrganizationRawContract;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
final class OrganizationRawService implements OrganizationRawContract
{
    // @phpstan-ignore-next-line
    /**
     * @internal
     */
    public function __construct(private Client $client) {}

    /**
     * @api
     *
     * Retrieve information about the organization associated with the authenticated API key.
     *
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaOrganization>
     *
     * @throws APIException
     */
    public function retrieve(
        RequestOptions|array|null $requestOptions = null
    ): BaseResponse {
        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'get',
            path: 'v1/organizations/me?beta=true',
            options: $requestOptions,
            convert: BetaOrganization::class,
        );
    }
}
