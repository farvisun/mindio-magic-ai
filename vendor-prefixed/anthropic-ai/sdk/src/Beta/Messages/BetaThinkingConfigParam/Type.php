<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaThinkingConfigParam;

enum Type: string
{
    case ENABLED = 'enabled';

    case DISABLED = 'disabled';

    case BETWEEN_TOOLS = 'between_tools';

    case ADAPTIVE = 'adaptive';
}
