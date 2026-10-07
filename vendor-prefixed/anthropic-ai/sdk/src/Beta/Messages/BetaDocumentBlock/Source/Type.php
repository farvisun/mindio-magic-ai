<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaDocumentBlock\Source;

enum Type: string
{
    case BASE64 = 'base64';

    case TEXT = 'text';
}
