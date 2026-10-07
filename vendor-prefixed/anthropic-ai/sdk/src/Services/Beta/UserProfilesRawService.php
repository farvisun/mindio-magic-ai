<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Services\Beta;

use MindioMagicAI\Vendor\Anthropic\Beta\AnthropicBeta;
use MindioMagicAI\Vendor\Anthropic\Beta\UserProfiles\BetaUserProfile;
use MindioMagicAI\Vendor\Anthropic\Beta\UserProfiles\BetaUserProfileEnrollmentURL;
use MindioMagicAI\Vendor\Anthropic\Beta\UserProfiles\BetaUserProfileExternalUserDetailsParams;
use MindioMagicAI\Vendor\Anthropic\Beta\UserProfiles\UserProfileCreateEnrollmentURLParams;
use MindioMagicAI\Vendor\Anthropic\Beta\UserProfiles\UserProfileCreateParams;
use MindioMagicAI\Vendor\Anthropic\Beta\UserProfiles\UserProfileCreateParams\AccessType;
use MindioMagicAI\Vendor\Anthropic\Beta\UserProfiles\UserProfileListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\UserProfiles\UserProfileListParams\Order;
use MindioMagicAI\Vendor\Anthropic\Beta\UserProfiles\UserProfileListParams\OrderBy;
use MindioMagicAI\Vendor\Anthropic\Beta\UserProfiles\UserProfileRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\UserProfiles\UserProfileUpdateParams;
use MindioMagicAI\Vendor\Anthropic\Client;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\Core\Util;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;
use MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\UserProfilesRawContract;

/**
 * @phpstan-import-type BetaUserProfileExternalUserDetailsParamsShape from \Anthropic\Beta\UserProfiles\BetaUserProfileExternalUserDetailsParams
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
final class UserProfilesRawService implements UserProfilesRawContract
{
    // @phpstan-ignore-next-line
    /**
     * @internal
     */
    public function __construct(private Client $client) {}

    /**
     * @api
     *
     * Create User Profile
     *
     * @param array{
     *   accessType?: AccessType|value-of<AccessType>,
     *   externalID?: string|null,
     *   externalUserDetails?: BetaUserProfileExternalUserDetailsParams|BetaUserProfileExternalUserDetailsParamsShape,
     *   externalUserOnboardedAt?: \DateTimeInterface,
     *   metadata?: array<string,string>,
     *   name?: string|null,
     *   betas?: list<string|AnthropicBeta|value-of<AnthropicBeta>>,
     *   workspaceID?: string,
     * }|UserProfileCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaUserProfile>
     *
     * @throws APIException
     */
    public function create(
        array|UserProfileCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse {
        [$parsed, $options] = UserProfileCreateParams::parseRequest(
            $params,
            $requestOptions,
        );
        $header_params = [
            'betas' => 'anthropic-beta', 'workspaceID' => 'anthropic-workspace-id',
        ];

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'post',
            path: 'v1/user_profiles?beta=true',
            headers: Util::array_transform_keys(
                array_intersect_key($parsed, array_flip(array_keys($header_params))),
                $header_params,
            ),
            body: (object) array_diff_key(
                $parsed,
                array_flip(array_keys($header_params))
            ),
            options: RequestOptions::parse(
                ['extraHeaders' => ['anthropic-beta' => 'user-profiles-2026-08-18']],
                $options,
            ),
            convert: BetaUserProfile::class,
        );
    }

    /**
     * @api
     *
     * Get User Profile
     *
     * @param string $userProfileID The ID of the user profile to get (`uprof_...`).
     * @param array{
     *   betas?: list<string|AnthropicBeta|value-of<AnthropicBeta>>,
     *   workspaceID?: string,
     * }|UserProfileRetrieveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaUserProfile>
     *
     * @throws APIException
     */
    public function retrieve(
        string $userProfileID,
        array|UserProfileRetrieveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse {
        [$parsed, $options] = UserProfileRetrieveParams::parseRequest(
            $params,
            $requestOptions,
        );

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'get',
            path: ['v1/user_profiles/%1$s?beta=true', $userProfileID],
            headers: Util::array_transform_keys(
                $parsed,
                [
                    'betas' => 'anthropic-beta', 'workspaceID' => 'anthropic-workspace-id',
                ],
            ),
            options: RequestOptions::parse(
                ['extraHeaders' => ['anthropic-beta' => 'user-profiles-2026-08-18']],
                $options,
            ),
            convert: BetaUserProfile::class,
        );
    }

    /**
     * @api
     *
     * Update User Profile
     *
     * @param string $userProfileID Path param: The ID of the user profile to update (`uprof_...`).
     * @param array{
     *   accessType?: UserProfileUpdateParams\AccessType|value-of<UserProfileUpdateParams\AccessType>|null,
     *   externalID?: string|null,
     *   externalUserDetails?: BetaUserProfileExternalUserDetailsParams|BetaUserProfileExternalUserDetailsParamsShape,
     *   externalUserOnboardedAt?: \DateTimeInterface,
     *   metadata?: array<string,string>,
     *   name?: string|null,
     *   betas?: list<string|AnthropicBeta|value-of<AnthropicBeta>>,
     *   workspaceID?: string,
     * }|UserProfileUpdateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaUserProfile>
     *
     * @throws APIException
     */
    public function update(
        string $userProfileID,
        array|UserProfileUpdateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse {
        [$parsed, $options] = UserProfileUpdateParams::parseRequest(
            $params,
            $requestOptions,
        );
        $header_params = [
            'betas' => 'anthropic-beta', 'workspaceID' => 'anthropic-workspace-id',
        ];

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'post',
            path: ['v1/user_profiles/%1$s?beta=true', $userProfileID],
            headers: Util::array_transform_keys(
                array_intersect_key($parsed, array_flip(array_keys($header_params))),
                $header_params,
            ),
            body: (object) array_diff_key(
                $parsed,
                array_flip(array_keys($header_params))
            ),
            options: RequestOptions::parse(
                ['extraHeaders' => ['anthropic-beta' => 'user-profiles-2026-08-18']],
                $options,
            ),
            convert: BetaUserProfile::class,
        );
    }

    /**
     * @api
     *
     * List User Profiles
     *
     * @param array{
     *   limit?: int,
     *   order?: Order|value-of<Order>,
     *   orderBy?: OrderBy|value-of<OrderBy>,
     *   page?: string,
     *   betas?: list<string|AnthropicBeta|value-of<AnthropicBeta>>,
     *   workspaceID?: string,
     * }|UserProfileListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<BetaUserProfile>>
     *
     * @throws APIException
     */
    public function list(
        array|UserProfileListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse {
        [$parsed, $options] = UserProfileListParams::parseRequest(
            $params,
            $requestOptions,
        );
        $query_params = array_flip(['limit', 'order', 'orderBy', 'page']);

        /** @var array<string,string> */
        $header_params = array_diff_key($parsed, $query_params);

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'get',
            path: 'v1/user_profiles?beta=true',
            query: Util::array_transform_keys(
                array_intersect_key($parsed, $query_params),
                ['orderBy' => 'order_by']
            ),
            headers: Util::array_transform_keys(
                $header_params,
                [
                    'betas' => 'anthropic-beta', 'workspaceID' => 'anthropic-workspace-id',
                ],
            ),
            options: RequestOptions::parse(
                ['extraHeaders' => ['anthropic-beta' => 'user-profiles-2026-08-18']],
                $options,
            ),
            convert: BetaUserProfile::class,
            page: PageCursor::class,
        );
    }

    /**
     * @api
     *
     * Create Enrollment URL
     *
     * @param string $userProfileID The ID of the user profile to create an enrollment URL for (`uprof_...`).
     * @param array{
     *   betas?: list<string|AnthropicBeta|value-of<AnthropicBeta>>,
     *   workspaceID?: string,
     * }|UserProfileCreateEnrollmentURLParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaUserProfileEnrollmentURL>
     *
     * @throws APIException
     */
    public function createEnrollmentURL(
        string $userProfileID,
        array|UserProfileCreateEnrollmentURLParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse {
        [$parsed, $options] = UserProfileCreateEnrollmentURLParams::parseRequest(
            $params,
            $requestOptions,
        );

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'post',
            path: ['v1/user_profiles/%1$s/enrollment_url?beta=true', $userProfileID],
            headers: Util::array_transform_keys(
                $parsed,
                [
                    'betas' => 'anthropic-beta', 'workspaceID' => 'anthropic-workspace-id',
                ],
            ),
            options: RequestOptions::parse(
                ['extraHeaders' => ['anthropic-beta' => 'user-profiles-2026-08-18']],
                $options,
            ),
            convert: BetaUserProfileEnrollmentURL::class,
        );
    }
}
