<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\PluginMarketplaces\PluginMarketplaceListParams;

/**
 * `organization` for the organization's plugin marketplaces, `user` for members' personal plugin marketplaces.
 */
enum OwnerType: string
{
    case ORGANIZATION = 'organization';

    case USER = 'user';
}
