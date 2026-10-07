<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\Plugins\Plugin\Owner;

enum Type: string
{
    case ORGANIZATION = 'organization';

    case USER = 'user';
}
