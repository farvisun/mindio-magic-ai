<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaWebFetchURLSources\ServerToolResults;

enum Type: string
{
    case ALL = 'all';

    case NONE = 'none';

    case ONLY = 'only';

    case EXCEPT = 'except';
}
