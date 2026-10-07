<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Lib\Credentials;

final class IdentityTokenLiteral implements IdentityTokenProvider
{
    public function __construct(private readonly string $token) {}

    public function getIdentityToken(): string
    {
        return $this->token;
    }
}
