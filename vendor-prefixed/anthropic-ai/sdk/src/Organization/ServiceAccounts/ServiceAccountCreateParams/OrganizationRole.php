<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Organization\ServiceAccounts\ServiceAccountCreateParams;

/**
 * Org-level role. Defaults to `developer`.
 */
enum OrganizationRole: string
{
    case ADMIN = 'admin';

    case DEVELOPER = 'developer';
}
