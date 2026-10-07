<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\Workspaces\RateLimits\BetaWorkspaceRateLimitValue\Source;

enum Type: string
{
    case WORKSPACE = 'workspace';

    case ORGANIZATION = 'organization';
}
