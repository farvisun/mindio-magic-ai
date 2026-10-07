<?php

declare (strict_types=1);
namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\ServiceAccounts;

use MindioMagicAI\Vendor\Anthropic\Beta\AnthropicBeta;
use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Optional;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;
/**
 * **Requires an OAuth access token with the `org:admin` scope**, from `ant auth login --scope org:admin` or a workload identity federation rule; Admin API keys are not accepted. See [Manage WIF with the Admin API](/docs/en/manage-claude/wif-admin-api).
 *
 * Retrieve a service account by its ID (`svac_...`).
 *
 * @see \MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization\ServiceAccountsService::retrieve()
 *
 * @phpstan-type ServiceAccountRetrieveParamsShape = array{
 *   betas?: list<string|AnthropicBeta|value-of<AnthropicBeta>>|null
 * }
 */
final class ServiceAccountRetrieveParams implements BaseModel
{
    /** @use SdkModel<ServiceAccountRetrieveParamsShape> */
    use SdkModel;
    use SdkParams;
    /**
     * Optional header to specify the beta version(s) you want to use.
     *
     * @var list<string|value-of<AnthropicBeta>>|null $betas
     */
    #[Optional(list: AnthropicBeta::class)]
    public ?array $betas;
    public function __construct()
    {
        $this->initialize();
    }
    /**
     * Construct an instance from the required parameters.
     *
     * You must use named parameters to construct any parameters with a default value.
     *
     * @param list<string|AnthropicBeta|value-of<AnthropicBeta>>|null $betas
     */
    public static function with(?array $betas = null): self
    {
        $self = new self();
        null !== $betas && $self['betas'] = $betas;
        return $self;
    }
    /**
     * Optional header to specify the beta version(s) you want to use.
     *
     * @param list<string|AnthropicBeta|value-of<AnthropicBeta>> $betas
     */
    public function withBetas(array $betas): self
    {
        $self = clone $this;
        $self['betas'] = $betas;
        return $self;
    }
}