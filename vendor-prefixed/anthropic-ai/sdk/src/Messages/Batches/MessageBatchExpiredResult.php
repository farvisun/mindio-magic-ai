<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages\Batches;

use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Required;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ConstantOf;

/**
 * @phpstan-type MessageBatchExpiredResultShape = array{type: 'expired'}
 */
final class MessageBatchExpiredResult implements BaseModel
{
    /** @use SdkModel<MessageBatchExpiredResultShape> */
    use SdkModel;

    /** @var 'expired' $type */
    #[Required(type: new ConstantOf('expired'))]
    public string $type = 'expired';

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
     * @param 'expired' $type
     */
    public function withType(string $type): self
    {
        $self = clone $this;
        $self['type'] = $type;

        return $self;
    }
}
