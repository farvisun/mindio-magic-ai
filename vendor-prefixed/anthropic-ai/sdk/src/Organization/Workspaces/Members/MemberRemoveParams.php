<?php

declare (strict_types=1);
namespace MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\Members;

use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Required;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;
/**
 * Delete Workspace Member.
 *
 * @see \MindioMagicAI\Vendor\Anthropic\Services\Organization\Workspaces\MembersService::remove()
 *
 * @phpstan-type MemberRemoveParamsShape = array{workspaceID: string}
 */
final class MemberRemoveParams implements BaseModel
{
    /** @use SdkModel<MemberRemoveParamsShape> */
    use SdkModel;
    use SdkParams;
    /**
     * ID of the Workspace.
     */
    #[Required]
    public string $workspaceID;
    /**
     * `new MemberRemoveParams()` is missing required properties by the API.
     *
     * To enforce required parameters use
     * ```
     * MemberRemoveParams::with(workspaceID: ...)
     * ```
     *
     * Otherwise ensure the following setters are called
     *
     * ```
     * (new MemberRemoveParams)->withWorkspaceID(...)
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
    public static function with(string $workspaceID): self
    {
        $self = new self();
        $self['workspaceID'] = $workspaceID;
        return $self;
    }
    /**
     * ID of the Workspace.
     */
    public function withWorkspaceID(string $workspaceID): self
    {
        $self = clone $this;
        $self['workspaceID'] = $workspaceID;
        return $self;
    }
}