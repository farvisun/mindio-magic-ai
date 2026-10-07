<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsGitHubRepositoryResourceParams\Checkout;

enum Type: string
{
    case BRANCH = 'branch';

    case COMMIT = 'commit';
}
