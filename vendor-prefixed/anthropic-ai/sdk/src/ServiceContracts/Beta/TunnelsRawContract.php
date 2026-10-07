<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta;

use MindioMagicAI\Vendor\Anthropic\Beta\Tunnels\BetaTunnel;
use MindioMagicAI\Vendor\Anthropic\Beta\Tunnels\BetaTunnelToken;
use MindioMagicAI\Vendor\Anthropic\Beta\Tunnels\TunnelArchiveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Tunnels\TunnelCreateParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Tunnels\TunnelListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Tunnels\TunnelRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Tunnels\TunnelRevealTokenParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Tunnels\TunnelRotateTokenParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface TunnelsRawContract
{
    /**
     * @api
     *
     * @param array<string,mixed>|TunnelCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaTunnel>
     *
     * @throws APIException
     */
    public function create(
        array|TunnelCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $tunnelID ID of the tunnel (`tnl_...`).
     * @param array<string,mixed>|TunnelRetrieveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaTunnel>
     *
     * @throws APIException
     */
    public function retrieve(
        string $tunnelID,
        array|TunnelRetrieveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param array<string,mixed>|TunnelListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<BetaTunnel>>
     *
     * @throws APIException
     */
    public function list(
        array|TunnelListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $tunnelID ID of the tunnel (`tnl_...`).
     * @param array<string,mixed>|TunnelArchiveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaTunnel>
     *
     * @throws APIException
     */
    public function archive(
        string $tunnelID,
        array|TunnelArchiveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $tunnelID ID of the tunnel (`tnl_...`).
     * @param array<string,mixed>|TunnelRevealTokenParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaTunnelToken>
     *
     * @throws APIException
     */
    public function revealToken(
        string $tunnelID,
        array|TunnelRevealTokenParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $tunnelID Path param: ID of the tunnel (`tnl_...`).
     * @param array<string,mixed>|TunnelRotateTokenParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaTunnelToken>
     *
     * @throws APIException
     */
    public function rotateToken(
        string $tunnelID,
        array|TunnelRotateTokenParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
