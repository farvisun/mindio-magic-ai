<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Environments;

use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Required;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ConstantOf;

/**
 * Configuration for self-hosted environments.
 *
 * @phpstan-type BetaSelfHostedConfigShape = array{type: 'self_hosted'}
 */
final class BetaSelfHostedConfig implements BaseModel
{
    /** @use SdkModel<BetaSelfHostedConfigShape> */
    use SdkModel;

    /**
     * Environment type.
     *
     * @var 'self_hosted' $type
     */
    #[Required(type: new ConstantOf('self_hosted'))]
    public string $type = 'self_hosted';

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
     * Environment type.
     *
     * @param 'self_hosted' $type
     */
    public function withType(string $type): self
    {
        $self = clone $this;
        $self['type'] = $type;

        return $self;
    }
}
