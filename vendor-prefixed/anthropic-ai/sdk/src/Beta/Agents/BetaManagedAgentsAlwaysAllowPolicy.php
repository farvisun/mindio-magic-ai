<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Agents;

use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsAlwaysAllowPolicy\Type;
use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Required;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;

/**
 * Tool calls are automatically approved without user confirmation.
 *
 * @phpstan-type BetaManagedAgentsAlwaysAllowPolicyShape = array{
 *   type: Type|value-of<Type>
 * }
 */
final class BetaManagedAgentsAlwaysAllowPolicy implements BaseModel
{
    /** @use SdkModel<BetaManagedAgentsAlwaysAllowPolicyShape> */
    use SdkModel;

    /** @var value-of<Type> $type */
    #[Required(enum: Type::class)]
    public string $type;

    /**
     * `new BetaManagedAgentsAlwaysAllowPolicy()` is missing required properties by the API.
     *
     * To enforce required parameters use
     * ```
     * BetaManagedAgentsAlwaysAllowPolicy::with(type: ...)
     * ```
     *
     * Otherwise ensure the following setters are called
     *
     * ```
     * (new BetaManagedAgentsAlwaysAllowPolicy)->withType(...)
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
    public static function with(Type|string $type): self
    {
        $self = new self;

        $self['type'] = $type;

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
