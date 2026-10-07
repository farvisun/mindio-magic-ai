<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaCompactionBlock\ToolChange;

enum Type: string
{
    case TOOL_ADDITION = 'tool_addition';

    case TOOL_REMOVAL = 'tool_removal';
}
