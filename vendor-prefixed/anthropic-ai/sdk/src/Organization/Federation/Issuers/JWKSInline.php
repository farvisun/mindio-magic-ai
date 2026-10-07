<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Organization\Federation\Issuers;

use MindioMagicAI\Vendor\Anthropic\Core\Attributes\Required;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkModel;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseModel;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ConstantOf;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\MapOf;

/**
 * JWKS supplied directly; no network fetch.
 *
 * @phpstan-type JWKSInlineShape = array{
 *   keys: list<array<string,mixed>>, type: 'inline'
 * }
 */
final class JWKSInline implements BaseModel
{
    /** @use SdkModel<JWKSInlineShape> */
    use SdkModel;

    /** @var 'inline' $type */
    #[Required(type: new ConstantOf('inline'))]
    public string $type = 'inline';

    /**
     * Inline JWK objects.
     *
     * @var list<array<string,mixed>> $keys
     */
    #[Required(list: new MapOf('mixed'))]
    public array $keys;

    /**
     * `new JWKSInline()` is missing required properties by the API.
     *
     * To enforce required parameters use
     * ```
     * JWKSInline::with(keys: ...)
     * ```
     *
     * Otherwise ensure the following setters are called
     *
     * ```
     * (new JWKSInline)->withKeys(...)
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
     * @param list<array<string,mixed>> $keys
     */
    public static function with(array $keys): self
    {
        $self = new self;

        $self['keys'] = $keys;

        return $self;
    }

    /**
     * Inline JWK objects.
     *
     * @param list<array<string,mixed>> $keys
     */
    public function withKeys(array $keys): self
    {
        $self = clone $this;
        $self['keys'] = $keys;

        return $self;
    }

    /**
     * @param 'inline' $type
     */
    public function withType(string $type): self
    {
        $self = clone $this;
        $self['type'] = $type;

        return $self;
    }
}
