<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization\Analytics;

use MindioMagicAI\Vendor\Anthropic\Client;
use MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Organization\Analytics\AppsRawContract;

final class AppsRawService implements AppsRawContract
{
    // @phpstan-ignore-next-line
    /**
     * @internal
     */
    public function __construct(private Client $client) {}
}
