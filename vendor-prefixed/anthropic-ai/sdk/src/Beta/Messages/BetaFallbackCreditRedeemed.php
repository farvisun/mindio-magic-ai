<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages;

use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Required;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ConstantOf;

/**
 * The reprice was applied: the retry is billed as if the conversation
 * had been on the retry model all along.
 *
 * @phpstan-type BetaFallbackCreditRedeemedShape = array{type: 'redeemed'}
 */
final class BetaFallbackCreditRedeemed implements BaseModel
{
    /** @use SdkModel<BetaFallbackCreditRedeemedShape> */
    use SdkModel;

    /** @var 'redeemed' $type */
    #[Required(type: new ConstantOf('redeemed'))]
    public string $type = 'redeemed';

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
     * @param 'redeemed' $type
     */
    public function withType(string $type): self
    {
        $self = clone $this;
        $self['type'] = $type;

        return $self;
    }
}
