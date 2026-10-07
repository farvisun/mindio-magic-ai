<?php

namespace MindioMagicAI\Vendor\Anthropic\Core\Exceptions;

use MindioMagicAI\Vendor\Psr\Http\Message\RequestInterface;

class APITimeoutException extends APIConnectionException
{
    /** @var string */
    protected const DESC = 'Anthropic API Timeout Exception';

    public function __construct(
        RequestInterface $request,
        ?\Throwable $previous = null,
        string $message = 'Request timed out.',
    ) {
        parent::__construct(request: $request, message: $message, previous: $previous);
    }
}
