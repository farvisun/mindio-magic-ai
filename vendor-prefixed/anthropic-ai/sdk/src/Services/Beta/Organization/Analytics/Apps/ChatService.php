<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization\Analytics\Apps;

use MindioMagicAI\Vendor\Anthropic\Client;
use MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Organization\Analytics\Apps\ChatContract;
use MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization\Analytics\Apps\Chat\ProjectsService;

final class ChatService implements ChatContract
{
    /**
     * @api
     */
    public ChatRawService $raw;

    /**
     * @api
     */
    public ProjectsService $projects;

    /**
     * @internal
     */
    public function __construct(private Client $client)
    {
        $this->raw = new ChatRawService($client);
        $this->projects = new ProjectsService($client);
    }
}
