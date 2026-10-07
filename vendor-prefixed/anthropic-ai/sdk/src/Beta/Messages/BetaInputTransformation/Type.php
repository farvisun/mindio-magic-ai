<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaInputTransformation;

enum Type: string
{
    case THINKING_DROPPED = 'thinking_dropped';

    case THINKING_MISMATCH_ALLOWED = 'thinking_mismatch_allowed';
}
