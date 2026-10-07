<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\APIKeys\APIKey\Scope;

enum Type: string
{
    case ORGANIZATION = 'organization';

    case WORKSPACE = 'workspace';
}
