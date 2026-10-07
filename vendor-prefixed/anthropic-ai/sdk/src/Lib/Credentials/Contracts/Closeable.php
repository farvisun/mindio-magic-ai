<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Lib\Credentials\Contracts;

interface Closeable
{
    /**
     * Release any held resources (e.g., HTTP clients).
     */
    public function close(): void;
}
