<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\DeploymentRuns;

use MindioMagicAI\Vendor\Anthropic\Beta\DeploymentRuns\BetaManagedAgentsOrganizationDisabledRunError\Type;
use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Required;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;

/**
 * The deployment's organization is disabled.
 *
 * @phpstan-type BetaManagedAgentsOrganizationDisabledRunErrorShape = array{
 *   message: string, type: Type|value-of<Type>
 * }
 */
final class BetaManagedAgentsOrganizationDisabledRunError implements BaseModel
{
    /** @use SdkModel<BetaManagedAgentsOrganizationDisabledRunErrorShape> */
    use SdkModel;

    /**
     * Human-readable error description.
     */
    #[Required]
    public string $message;

    /** @var value-of<Type> $type */
    #[Required(enum: Type::class)]
    public string $type;

    /**
     * `new BetaManagedAgentsOrganizationDisabledRunError()` is missing required properties by the API.
     *
     * To enforce required parameters use
     * ```
     * BetaManagedAgentsOrganizationDisabledRunError::with(message: ..., type: ...)
     * ```
     *
     * Otherwise ensure the following setters are called
     *
     * ```
     * (new BetaManagedAgentsOrganizationDisabledRunError)
     *   ->withMessage(...)
     *   ->withType(...)
     * ```
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Construct an instance from the required parameters.
     *
     * You must use named parameters to construct any parameters with a default value.
     *
     * @param Type|value-of<Type> $type
     */
    public static function with(string $message, Type|string $type): self
    {
        $self = new self;

        $self['message'] = $message;
        $self['type'] = $type;

        return $self;
    }

    /**
     * Human-readable error description.
     */
    public function withMessage(string $message): self
    {
        $self = clone $this;
        $self['message'] = $message;

        return $self;
    }

    /**
     * @param Type|value-of<Type> $type
     */
    public function withType(Type|string $type): self
    {
        $self = clone $this;
        $self['type'] = $type;

        return $self;
    }
}
