<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaCacheControlEphemeral\TTL;
use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Optional;
use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Required;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ConstantOf;

/**
 * @phpstan-type BetaCacheControlEphemeralShape = array{
 *   type: 'ephemeral', ttl?: null|TTL|value-of<TTL>
 * }
 */
final class BetaCacheControlEphemeral implements BaseModel
{
    /** @use SdkModel<BetaCacheControlEphemeralShape> */
    use SdkModel;

    /** @var 'ephemeral' $type */
    #[Required(type: new ConstantOf('ephemeral'))]
    public string $type = 'ephemeral';

    /**
     * The time-to-live for the cache control breakpoint.
     *
     * This may be one the following values:
     * - `5m`: 5 minutes
     * - `1h`: 1 hour
     *
     * Defaults to `5m`. See [prompt caching pricing](https://platform.claude.com/docs/en/build-with-claude/prompt-caching) for details.
     *
     * @var value-of<TTL>|null $ttl
     */
    #[Optional(enum: TTL::class)]
    public ?string $ttl;

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Construct an instance from the required parameters.
     *
     * You must use named parameters to construct any parameters with a default value.
     *
     * @param TTL|value-of<TTL>|null $ttl
     */
    public static function with(TTL|string|null $ttl = null): self
    {
        $self = new self;

        null !== $ttl && $self['ttl'] = $ttl;

        return $self;
    }

    /**
     * @param 'ephemeral' $type
     */
    public function withType(string $type): self
    {
        $self = clone $this;
        $self['type'] = $type;

        return $self;
    }

    /**
     * The time-to-live for the cache control breakpoint.
     *
     * This may be one the following values:
     * - `5m`: 5 minutes
     * - `1h`: 1 hour
     *
     * Defaults to `5m`. See [prompt caching pricing](https://platform.claude.com/docs/en/build-with-claude/prompt-caching) for details.
     *
     * @param TTL|value-of<TTL> $ttl
     */
    public function withTTL(TTL|string $ttl): self
    {
        $self = clone $this;
        $self['ttl'] = $ttl;

        return $self;
    }
}
