<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages;

use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Required;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;
use MindioMagicAI\Vendor\Anthropic\Messages\MessageParam\Content;
use MindioMagicAI\Vendor\Anthropic\Messages\MessageParam\Role;

/**
 * @phpstan-import-type ContentVariants from \Anthropic\Messages\MessageParam\Content
 * @phpstan-import-type ContentShape from \Anthropic\Messages\MessageParam\Content
 *
 * @phpstan-type MessageParamShape = array{
 *   content: ContentShape, role: Role|value-of<Role>
 * }
 */
final class MessageParam implements BaseModel
{
    /** @use SdkModel<MessageParamShape> */
    use SdkModel;

    /** @var ContentVariants $content */
    #[Required(union: Content::class)]
    public string|array $content;

    /** @var value-of<Role> $role */
    #[Required(enum: Role::class)]
    public string $role;

    /**
     * `new MessageParam()` is missing required properties by the API.
     *
     * To enforce required parameters use
     * ```
     * MessageParam::with(content: ..., role: ...)
     * ```
     *
     * Otherwise ensure the following setters are called
     *
     * ```
     * (new MessageParam)->withContent(...)->withRole(...)
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
     * @param ContentShape $content
     * @param Role|value-of<Role> $role
     */
    public static function with(string|array $content, Role|string $role): self
    {
        $self = new self;

        $self['content'] = $content;
        $self['role'] = $role;

        return $self;
    }

    /**
     * @param ContentShape $content
     */
    public function withContent(string|array $content): self
    {
        $self = clone $this;
        $self['content'] = $content;

        return $self;
    }

    /**
     * @param Role|value-of<Role> $role
     */
    public function withRole(Role|string $role): self
    {
        $self = clone $this;
        $self['role'] = $role;

        return $self;
    }
}
