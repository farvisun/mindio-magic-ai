<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Dreams\BetaOutputBehavior;

enum Type: string
{
    case CREATE_NEW = 'create_new';

    case UPDATE_EXISTING = 'update_existing';
}
