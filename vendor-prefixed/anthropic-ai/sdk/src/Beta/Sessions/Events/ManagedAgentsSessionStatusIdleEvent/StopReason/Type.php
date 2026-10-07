<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionStatusIdleEvent\StopReason;

enum Type: string
{
    case END_TURN = 'end_turn';

    case REQUIRES_ACTION = 'requires_action';

    case RETRIES_EXHAUSTED = 'retries_exhausted';

    case BUDGET_REACHED = 'budget_reached';

    case REFUSAL = 'refusal';
}
