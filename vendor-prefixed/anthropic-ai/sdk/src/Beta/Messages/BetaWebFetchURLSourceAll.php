<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages;

use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Required;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ConstantOf;

/**
 * The ``url_sources`` variant under which a source contributes in
 * full: every result of the tool filter's source, or all user input.
 *
 * @phpstan-type BetaWebFetchURLSourceAllShape = array{type: 'all'}
 */
final class BetaWebFetchURLSourceAll implements BaseModel
{
    /** @use SdkModel<BetaWebFetchURLSourceAllShape> */
    use SdkModel;

    /** @var 'all' $type */
    #[Required(type: new ConstantOf('all'))]
    public string $type = 'all';

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
     * @param 'all' $type
     */
    public function withType(string $type): self
    {
        $self = clone $this;
        $self['type'] = $type;

        return $self;
    }
}
