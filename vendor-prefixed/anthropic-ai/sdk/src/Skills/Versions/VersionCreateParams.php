<?php

declare (strict_types=1);
namespace MindioMagicAI\Vendor\Anthropic\Skills\Versions;

use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Optional;
use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Required;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;
use MindioMagicAI\Vendor\Anthropic\Core\FileParam;
/**
 * Create Skill Version.
 *
 * @see \MindioMagicAI\Vendor\Anthropic\Services\Skills\VersionsService::create()
 *
 * @phpstan-type VersionCreateParamsShape = array{
 *   files: list<string|FileParam>, workspaceID?: string|null
 * }
 */
final class VersionCreateParams implements BaseModel
{
    /** @use SdkModel<VersionCreateParamsShape> */
    use SdkModel;
    use SdkParams;
    /**
     * Files to upload for the skill.
     *
     * All files must be in the same top-level directory and must include a SKILL.md file at the root of that directory.
     *
     * @var list<string> $files
     */
    #[Required(list: FileParam::class)]
    public array $files;
    /**
     * Optional header to select the Workspace for this request. The value is a Workspace ID (for example, `wrkspc_011CZkZaBF1tNoB5wlCeusgy`).
     *
     * Only needed for credentials that can act on more than one Workspace. A credential that belongs to a specific Workspace may omit it; if sent, it must match that Workspace.
     */
    #[Optional]
    public ?string $workspaceID;
    /**
     * `new VersionCreateParams()` is missing required properties by the API.
     *
     * To enforce required parameters use
     * ```
     * VersionCreateParams::with(files: ...)
     * ```
     *
     * Otherwise ensure the following setters are called
     *
     * ```
     * (new VersionCreateParams)->withFiles(...)
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
     *
     * @param list<string|FileParam> $files
     */
    public static function with(array $files, ?string $workspaceID = null): self
    {
        $self = new self();
        $self['files'] = $files;
        null !== $workspaceID && $self['workspaceID'] = $workspaceID;
        return $self;
    }
    /**
     * Files to upload for the skill.
     *
     * All files must be in the same top-level directory and must include a SKILL.md file at the root of that directory.
     *
     * @param list<string|FileParam> $files
     */
    public function withFiles(array $files): self
    {
        $self = clone $this;
        $self['files'] = $files;
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