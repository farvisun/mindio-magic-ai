<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\SpendLimits\IncreaseRequests;

enum BetaSpendLimitIncreaseRequestStatus: string
{
    case APPROVED = 'approved';

    case DENIED = 'denied';

    case PENDING = 'pending';
}
