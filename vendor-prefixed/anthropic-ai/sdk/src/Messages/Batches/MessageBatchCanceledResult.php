<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages\Batches;

use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Required;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ConstantOf;

/**
 * @phpstan-type MessageBatchCanceledResultShape = array{type: 'canceled'}
 */
final class MessageBatchCanceledResult implements BaseModel
{
    /** @use SdkModel<MessageBatchCanceledResultShape> */
    use SdkModel;

    /** @var 'canceled' $type */
    #[Required(type: new ConstantOf('canceled'))]
    public string $type = 'canceled';

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
     * @param 'canceled' $type
     */
    public function withType(string $type): self
    {
        $self = clone $this;
        $self['type'] = $type;

        return $self;
    }
}
