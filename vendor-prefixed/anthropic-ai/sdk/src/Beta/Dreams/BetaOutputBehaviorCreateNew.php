<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Dreams;

use MindioMagicAI\Vendor\Anthropic\Beta\Dreams\BetaOutputBehaviorCreateNew\Type;
use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Required;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;

/**
 * Write the result to a new memory store that starts as a copy of the input memory store. This is the default.
 *
 * The new memory store is in the same workspace as the dream. The dream doesn't change the input memory store.
 *
 * @phpstan-type BetaOutputBehaviorCreateNewShape = array{
 *   type: Type|value-of<Type>
 * }
 */
final class BetaOutputBehaviorCreateNew implements BaseModel
{
    /** @use SdkModel<BetaOutputBehaviorCreateNewShape> */
    use SdkModel;

    /** @var value-of<Type> $type */
    #[Required(enum: Type::class)]
    public string $type;

    /**
     * `new BetaOutputBehaviorCreateNew()` is missing required properties by the API.
     *
     * To enforce required parameters use
     * ```
     * BetaOutputBehaviorCreateNew::with(type: ...)
     * ```
     *
     * Otherwise ensure the following setters are called
     *
     * ```
     * (new BetaOutputBehaviorCreateNew)->withType(...)
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
