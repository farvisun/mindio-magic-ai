<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Resources\ManagedAgentsGitHubRepositoryResource\Checkout;

enum Type: string
{
    case BRANCH = 'branch';

    case COMMIT = 'commit';
}
