<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\Invites\OrganizationInvite;

/**
 * Status of the Invite.
 */
enum Status: string
{
    case ACCEPTED = 'accepted';

    case DELETED = 'deleted';

    case EXPIRED = 'expired';

    case PENDING = 'pending';
}
