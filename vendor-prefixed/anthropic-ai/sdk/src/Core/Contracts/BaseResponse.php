<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Core\Contracts;

use MindioMagicAI\Vendor\Psr\Http\Message\RequestInterface;
use MindioMagicAI\Vendor\Psr\Http\Message\ResponseInterface;

/**
 * @template R
 */
interface BaseResponse extends ResponseInterface
{
    public function getRequest(): RequestInterface;

    public function getRequestID(): ?string;

    public function getWorkspaceID(): ?string;

    /**
     * @return R
     */
    public function parse(): mixed;
}
