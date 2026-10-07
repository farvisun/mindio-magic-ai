<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Deployments\BetaManagedAgentsSessionResourceConfig;

enum Type: string
{
    case GITHUB_REPOSITORY = 'github_repository';

    case FILE = 'file';

    case MEMORY_STORE = 'memory_store';
}
