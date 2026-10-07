<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Services\Organization;

use MindioMagicAI\Vendor\Anthropic\Client;
use MindioMagicAI\Vendor\Anthropic\ServiceContracts\Organization\FederationContract;
use MindioMagicAI\Vendor\Anthropic\Services\Organization\Federation\IssuersService;
use MindioMagicAI\Vendor\Anthropic\Services\Organization\Federation\RulesService;

final class FederationService implements FederationContract
{
    /**
     * @api
     */
    public FederationRawService $raw;

    /**
     * @api
     */
    public IssuersService $issuers;

    /**
     * @api
     */
    public RulesService $rules;

    /**
     * @internal
     */
    public function __construct(private Client $client)
    {
        $this->raw = new FederationRawService($client);
        $this->issuers = new IssuersService($client);
        $this->rules = new RulesService($client);
    }
}
