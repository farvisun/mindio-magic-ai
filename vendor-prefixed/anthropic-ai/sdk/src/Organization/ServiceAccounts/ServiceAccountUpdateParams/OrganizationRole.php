<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Organization\ServiceAccounts\ServiceAccountUpdateParams;

/**
 * Replaces the org-level role. Omit or send `null` to leave unchanged.
 */
enum OrganizationRole: string
{
    case ADMIN = 'admin';

    case DEVELOPER = 'developer';
}
