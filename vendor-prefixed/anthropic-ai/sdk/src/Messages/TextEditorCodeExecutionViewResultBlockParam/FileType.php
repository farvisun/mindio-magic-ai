<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages\TextEditorCodeExecutionViewResultBlockParam;

enum FileType: string
{
    case TEXT = 'text';

    case IMAGE = 'image';

    case PDF = 'pdf';
}
