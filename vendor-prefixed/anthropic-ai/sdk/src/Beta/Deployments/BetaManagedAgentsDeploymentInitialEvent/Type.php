<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Deployments\BetaManagedAgentsDeploymentInitialEvent;

enum Type: string
{
    case USER_MESSAGE = 'user.message';

    case USER_DEFINE_OUTCOME = 'user.define_outcome';

    case SYSTEM_MESSAGE = 'system.message';
}
