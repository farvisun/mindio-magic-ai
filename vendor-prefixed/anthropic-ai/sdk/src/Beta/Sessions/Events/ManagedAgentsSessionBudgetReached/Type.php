<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionBudgetReached;

enum Type: string
{
    case BUDGET_REACHED = 'budget_reached';
}
