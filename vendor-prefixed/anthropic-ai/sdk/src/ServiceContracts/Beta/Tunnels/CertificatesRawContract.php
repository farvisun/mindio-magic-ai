<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Tunnels;

use MindioMagicAI\Vendor\Anthropic\Beta\Tunnels\Certificates\CertificateArchiveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Tunnels\Certificates\CertificateCreateParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Tunnels\Certificates\CertificateListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Tunnels\Certificates\CertificateRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Tunnels\Certificates\TunnelCertificate;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface CertificatesRawContract
{
    /**
     * @api
     *
     * @param string $tunnelID Path param: ID of the tunnel (`tnl_...`).
     * @param array<string,mixed>|CertificateCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<TunnelCertificate>
     *
     * @throws APIException
     */
    public function create(
        string $tunnelID,
        array|CertificateCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $certificateID Path param: ID of the certificate (`tcrt_...`).
     * @param array<string,mixed>|CertificateRetrieveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<TunnelCertificate>
     *
     * @throws APIException
     */
    public function retrieve(
        string $certificateID,
        array|CertificateRetrieveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $tunnelID Path param: ID of the tunnel (`tnl_...`).
     * @param array<string,mixed>|CertificateListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<TunnelCertificate>>
     *
     * @throws APIException
     */
    public function list(
        string $tunnelID,
        array|CertificateListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $certificateID Path param: ID of the certificate to archive (`tcrt_...`).
     * @param array<string,mixed>|CertificateArchiveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<TunnelCertificate>
     *
     * @throws APIException
     */
    public function archive(
        string $certificateID,
        array|CertificateArchiveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
