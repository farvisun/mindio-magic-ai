<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages;

use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Optional;
use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Required;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ConstantOf;

/**
 * @phpstan-type BetaAdvisorResultBlockParamShape = array{
 *   text: string, type: 'advisor_result', stopReason?: string|null
 * }
 */
final class BetaAdvisorResultBlockParam implements BaseModel
{
    /** @use SdkModel<BetaAdvisorResultBlockParamShape> */
    use SdkModel;

    /** @var 'advisor_result' $type */
    #[Required(type: new ConstantOf('advisor_result'))]
    public string $type = 'advisor_result';

    #[Required]
    public string $text;

    #[Optional('stop_reason', nullable: true)]
    public ?string $stopReason;

    /**
     * `new BetaAdvisorResultBlockParam()` is missing required properties by the API.
     *
     * To enforce required parameters use
     * ```
     * BetaAdvisorResultBlockParam::with(text: ...)
     * ```
     *
     * Otherwise ensure the following setters are called
     *
     * ```
     * (new BetaAdvisorResultBlockParam)->withText(...)
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
     */
    public static function with(string $text, ?string $stopReason = null): self
    {
        $self = new self;

        $self['text'] = $text;

        null !== $stopReason && $self['stopReason'] = $stopReason;

        return $self;
    }

    public function withText(string $text): self
    {
        $self = clone $this;
        $self['text'] = $text;

        return $self;
    }

    /**
     * @param 'advisor_result' $type
     */
    public function withType(string $type): self
    {
        $self = clone $this;
        $self['type'] = $type;

        return $self;
    }

    public function withStopReason(?string $stopReason): self
    {
        $self = clone $this;
        $self['stopReason'] = $stopReason;

        return $self;
    }
}
