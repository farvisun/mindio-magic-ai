<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages\ContentBlockSourceContent;

enum Type: string
{
    case TEXT = 'text';

    case IMAGE = 'image';
}
