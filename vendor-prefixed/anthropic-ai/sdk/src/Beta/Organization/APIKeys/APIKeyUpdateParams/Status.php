<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\APIKeys\APIKeyUpdateParams;

/**
 * Status of the API key.
 */
enum Status: string
{
    case ACTIVE = 'active';

    case ARCHIVED = 'archived';

    case INACTIVE = 'inactive';
}
