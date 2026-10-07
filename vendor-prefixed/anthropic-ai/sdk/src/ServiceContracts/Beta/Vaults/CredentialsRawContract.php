<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Vaults;

use MindioMagicAI\Vendor\Anthropic\Beta\Vaults\Credentials\CredentialArchiveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Vaults\Credentials\CredentialCreateParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Vaults\Credentials\CredentialDeleteParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Vaults\Credentials\CredentialListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Vaults\Credentials\CredentialMCPOAuthValidateParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Vaults\Credentials\CredentialRetrieveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Vaults\Credentials\CredentialUpdateParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Vaults\Credentials\ManagedAgentsCredential;
use MindioMagicAI\Vendor\Anthropic\Beta\Vaults\Credentials\ManagedAgentsCredentialValidation;
use MindioMagicAI\Vendor\Anthropic\Beta\Vaults\Credentials\ManagedAgentsDeletedCredential;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface CredentialsRawContract
{
    /**
     * @api
     *
     * @param string $vaultID path param: Identifier of the vault to create the credential in
     * @param array<string,mixed>|CredentialCreateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ManagedAgentsCredential>
     *
     * @throws APIException
     */
    public function create(
        string $vaultID,
        array|CredentialCreateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $credentialID path param: Unique identifier of the credential to retrieve
     * @param array<string,mixed>|CredentialRetrieveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ManagedAgentsCredential>
     *
     * @throws APIException
     */
    public function retrieve(
        string $credentialID,
        array|CredentialRetrieveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $credentialID path param: Unique identifier of the credential to update
     * @param array<string,mixed>|CredentialUpdateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ManagedAgentsCredential>
     *
     * @throws APIException
     */
    public function update(
        string $credentialID,
        array|CredentialUpdateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $vaultID path param: Identifier of the vault to list credentials for
     * @param array<string,mixed>|CredentialListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<ManagedAgentsCredential>>
     *
     * @throws APIException
     */
    public function list(
        string $vaultID,
        array|CredentialListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $credentialID path param: Unique identifier of the credential to delete
     * @param array<string,mixed>|CredentialDeleteParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ManagedAgentsDeletedCredential>
     *
     * @throws APIException
     */
    public function delete(
        string $credentialID,
        array|CredentialDeleteParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $credentialID path param: Unique identifier of the credential to archive
     * @param array<string,mixed>|CredentialArchiveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ManagedAgentsCredential>
     *
     * @throws APIException
     */
    public function archive(
        string $credentialID,
        array|CredentialArchiveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $credentialID path param: Unique identifier of the credential to validate
     * @param array<string,mixed>|CredentialMCPOAuthValidateParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ManagedAgentsCredentialValidation>
     *
     * @throws APIException
     */
    public function mcpOAuthValidate(
        string $credentialID,
        array|CredentialMCPOAuthValidateParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
