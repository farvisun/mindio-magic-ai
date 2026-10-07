<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Lib\Credentials;

use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\AnthropicException;

class OAuthException extends AnthropicException
{
    /** @var string */
    protected const DESC = 'Anthropic OAuth Error';

    public function __construct(
        public readonly int $statusCode,
        string $message,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
