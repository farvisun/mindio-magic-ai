<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\DeploymentRuns\BetaManagedAgentsTriggerContext;

enum Type: string
{
    case SCHEDULE = 'schedule';

    case MANUAL = 'manual';
}
