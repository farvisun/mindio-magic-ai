<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Deployments\BetaManagedAgentsDeploymentPausedReason;

enum Type: string
{
    case MANUAL = 'manual';

    case ERROR = 'error';
}
