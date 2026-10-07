<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events;

use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Required;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ConstantOf;

/**
 * The turn ended because the model's response was refused, for example by a safety classifier.
 *
 * @phpstan-type ManagedAgentsSessionRefusalShape = array{type: 'refusal'}
 */
final class ManagedAgentsSessionRefusal implements BaseModel
{
    /** @use SdkModel<ManagedAgentsSessionRefusalShape> */
    use SdkModel;

    /** @var 'refusal' $type */
    #[Required(type: new ConstantOf('refusal'))]
    public string $type = 'refusal';

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Construct an instance from the required parameters.
     *
     * You must use named parameters to construct any parameters with a default value.
     */
    public static function with(): self
    {
        return new self;
    }

    /**
     * @param 'refusal' $type
     */
    public function withType(string $type): self
    {
        $self = clone $this;
        $self['type'] = $type;

        return $self;
    }
}
