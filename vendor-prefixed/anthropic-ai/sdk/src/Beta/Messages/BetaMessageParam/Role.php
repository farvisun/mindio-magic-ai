<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaMessageParam;

enum Role: string
{
    case USER = 'user';

    case ASSISTANT = 'assistant';

    case SYSTEM = 'system';
}
