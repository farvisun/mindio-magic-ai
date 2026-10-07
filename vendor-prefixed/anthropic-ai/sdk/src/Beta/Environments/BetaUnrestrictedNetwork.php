<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Environments;

use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Required;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ConstantOf;

/**
 * Unrestricted network access.
 *
 * @phpstan-type BetaUnrestrictedNetworkShape = array{type: 'unrestricted'}
 */
final class BetaUnrestrictedNetwork implements BaseModel
{
    /** @use SdkModel<BetaUnrestrictedNetworkShape> */
    use SdkModel;

    /**
     * Network policy type.
     *
     * @var 'unrestricted' $type
     */
    #[Required(type: new ConstantOf('unrestricted'))]
    public string $type = 'unrestricted';

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
     * Network policy type.
     *
     * @param 'unrestricted' $type
     */
    public function withType(string $type): self
    {
        $self = clone $this;
        $self['type'] = $type;

        return $self;
    }
}
