<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Services\Organization;

use MindioMagicAI\Vendor\Anthropic\Client;
use MindioMagicAI\Vendor\Anthropic\ServiceContracts\Organization\FederationRawContract;

final class FederationRawService implements FederationRawContract
{
    // @phpstan-ignore-next-line
    /**
     * @internal
     */
    public function __construct(private Client $client) {}
}
