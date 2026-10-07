<?php

declare (strict_types=1);
namespace MindioMagicAI\Vendor\Anthropic\Skills;

use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Optional;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;
/**
 * Delete Skill.
 *
 * @see \MindioMagicAI\Vendor\Anthropic\Services\SkillsService::delete()
 *
 * @phpstan-type SkillDeleteParamsShape = array{workspaceID?: string|null}
 */
final class SkillDeleteParams implements BaseModel
{
    /** @use SdkModel<SkillDeleteParamsShape> */
    use SdkModel;
    use SdkParams;
    /**
     * Optional header to select the Workspace for this request. The value is a Workspace ID (for example, `wrkspc_011CZkZaBF1tNoB5wlCeusgy`).
     *
     * Only needed for credentials that can act on more than one Workspace. A credential that belongs to a specific Workspace may omit it; if sent, it must match that Workspace.
     */
    #[Optional]
    public ?string $workspaceID;
    public function __construct()
    {
        $this->initialize();
    }
    /**
     * Construct an instance from the required parameters.
     *
     * You must use named parameters to construct any parameters with a default value.
     */
    public static function with(?string $workspaceID = null): self
    {
        $self = new self();
        null !== $workspaceID && $self['workspaceID'] = $workspaceID;
        return $self;
    }
    /**
     * Optional header to select the Workspace for this request. The value is a Workspace ID (for example, `wrkspc_011CZkZaBF1tNoB5wlCeusgy`).
     *
     * Only needed for credentials that can act on more than one Workspace. A credential that belongs to a specific Workspace may omit it; if sent, it must match that Workspace.
     */
    public function withWorkspaceID(string $workspaceID): self
    {
        $self = clone $this;
        $self['workspaceID'] = $workspaceID;
        return $self;
    }
}