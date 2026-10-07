<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaContextManagementConfig\Edit;
use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Optional;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;

/**
 * @phpstan-import-type EditVariants from \Anthropic\Beta\Messages\BetaContextManagementConfig\Edit
 * @phpstan-import-type EditShape from \Anthropic\Beta\Messages\BetaContextManagementConfig\Edit
 *
 * @phpstan-type BetaContextManagementConfigShape = array{
 *   edits?: list<EditShape>|null
 * }
 */
final class BetaContextManagementConfig implements BaseModel
{
    /** @use SdkModel<BetaContextManagementConfigShape> */
    use SdkModel;

    /**
     * List of context management edits to apply.
     *
     * @var list<EditVariants>|null $edits
     */
    #[Optional(list: Edit::class)]
    public ?array $edits;

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Construct an instance from the required parameters.
     *
     * You must use named parameters to construct any parameters with a default value.
     *
     * @param list<EditShape>|null $edits
     */
    public static function with(?array $edits = null): self
    {
        $self = new self;

        null !== $edits && $self['edits'] = $edits;

        return $self;
    }

    /**
     * List of context management edits to apply.
     *
     * @param list<EditShape> $edits
     */
    public function withEdits(array $edits): self
    {
        $self = clone $this;
        $self['edits'] = $edits;

        return $self;
    }
}
