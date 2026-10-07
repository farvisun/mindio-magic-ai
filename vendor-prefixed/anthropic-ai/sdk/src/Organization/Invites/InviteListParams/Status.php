<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Organization\Invites\InviteListParams;

enum Status: string
{
    case ACCEPTED = 'accepted';

    case EXPIRED = 'expired';

    case PENDING = 'pending';
}
