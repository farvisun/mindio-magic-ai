<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\PluginMarketplaces\PluginMarketplace\Owner;

enum Type: string
{
    case ORGANIZATION = 'organization';

    case USER = 'user';
}
