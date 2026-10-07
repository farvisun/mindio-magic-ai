<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\Plugins\InstallationSettings\InstallationSettingListParams;

/**
 * Only settings for this kind of target: `organization` (the organization-wide setting) or `rbac_group` (an RBAC Group's).
 */
enum TargetType: string
{
    case ORGANIZATION = 'organization';

    case RBAC_GROUP = 'rbac_group';
}
