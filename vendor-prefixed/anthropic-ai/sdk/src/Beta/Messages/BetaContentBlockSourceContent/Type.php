<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaContentBlockSourceContent;

enum Type: string
{
    case TEXT = 'text';

    case IMAGE = 'image';
}
