<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Services;

use MindioMagicAI\Vendor\Anthropic\Client;
use MindioMagicAI\Vendor\Anthropic\ServiceContracts\BetaRawContract;

final class BetaRawService implements BetaRawContract
{
    // @phpstan-ignore-next-line
    /**
     * @internal
     */
    public function __construct(private Client $client) {}
}
