<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization\Analytics;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Analytics\AnalyticsArtifactActivity;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Analytics\Artifacts\ArtifactListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Analytics\Artifacts\ArtifactListParams\GroupBy;
use MindioMagicAI\Vendor\Anthropic\Client;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\Core\Util;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;
use MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Organization\Analytics\ArtifactsRawContract;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
final class ArtifactsRawService implements ArtifactsRawContract
{
    // @phpstan-ignore-next-line
    /**
     * @internal
     */
    public function __construct(private Client $client) {}

    /**
     * @api
     *
     * Get artifact-creation activity for a given day, broken out by MIME type.
     *
     * Returns the full (`artifact_type`, `is_shared`) cube for the organization;
     * `next_page` is null except for grouped queries, which paginate. The cube
     * can be broken out per product, per member, or per RBAC group via
     * `group_by[]`, and scoped via `filter[]`. Requires an API key with the
     * `read:analytics` scope.
     *
     * @param array{
     *   date: string,
     *   filter?: list<string>|null,
     *   groupBy?: list<GroupBy|value-of<GroupBy>>|null,
     *   limit?: int|null,
     *   page?: string|null,
     * }|ArtifactListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<AnalyticsArtifactActivity>>
     *
     * @throws APIException
     */
    public function list(
        array|ArtifactListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse {
        [$parsed, $options] = ArtifactListParams::parseRequest(
            $params,
            $requestOptions,
        );

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'get',
            path: 'v1/organizations/analytics/artifacts?beta=true',
            query: Util::array_transform_keys($parsed, ['groupBy' => 'group_by']),
            options: $options,
            convert: AnalyticsArtifactActivity::class,
            page: PageCursor::class,
        );
    }
}
