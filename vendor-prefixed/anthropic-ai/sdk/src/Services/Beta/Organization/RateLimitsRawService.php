<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\RateLimits\OrganizationRateLimit;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\RateLimits\RateLimitListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\RateLimits\RateLimitListParams\GroupType;
use MindioMagicAI\Vendor\Anthropic\Client;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\Core\Util;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;
use MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Organization\RateLimitsRawContract;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
final class RateLimitsRawService implements RateLimitsRawContract
{
    // @phpstan-ignore-next-line
    /**
     * @internal
     */
    public function __construct(private Client $client) {}

    /**
     * @api
     *
     * List Messages API rate limits for your organization.
     *
     * Each entry corresponds to one rate-limit group (either a model family
     * or an API-surface category such as the Files API or Message Batches)
     * and contains the set of limiter values that apply to it.
     *
     * When `limit` is omitted, every matching entry is returned in a single
     * page; when `limit` truncates the result, follow `next_page` to fetch
     * the remaining entries.
     *
     * @param array{
     *   groupType?: GroupType|value-of<GroupType>|null,
     *   limit?: int|null,
     *   model?: string|null,
     *   page?: string|null,
     * }|RateLimitListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<OrganizationRateLimit>>
     *
     * @throws APIException
     */
    public function list(
        array|RateLimitListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse {
        [$parsed, $options] = RateLimitListParams::parseRequest(
            $params,
            $requestOptions,
        );

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'get',
            path: 'v1/organizations/rate_limits?beta=true',
            query: Util::array_transform_keys($parsed, ['groupType' => 'group_type']),
            options: $options,
            convert: OrganizationRateLimit::class,
            page: PageCursor::class,
        );
    }
}
