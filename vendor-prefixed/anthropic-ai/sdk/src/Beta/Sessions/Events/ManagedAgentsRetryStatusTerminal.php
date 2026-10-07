<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events;

use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsRetryStatusTerminal\Type;
use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Required;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;

/**
 * The session encountered a terminal error and will transition to `terminated` state.
 *
 * @phpstan-type ManagedAgentsRetryStatusTerminalShape = array{
 *   type: Type|value-of<Type>
 * }
 */
final class ManagedAgentsRetryStatusTerminal implements BaseModel
{
    /** @use SdkModel<ManagedAgentsRetryStatusTerminalShape> */
    use SdkModel;

    /** @var value-of<Type> $type */
    #[Required(enum: Type::class)]
    public string $type;

    /**
     * `new ManagedAgentsRetryStatusTerminal()` is missing required properties by the API.
     *
     * To enforce required parameters use
     * ```
     * ManagedAgentsRetryStatusTerminal::with(type: ...)
     * ```
     *
     * Otherwise ensure the following setters are called
     *
     * ```
     * (new ManagedAgentsRetryStatusTerminal)->withType(...)
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
