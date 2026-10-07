<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\ExternalKeys\ExternalKey\Attachment;

enum Type: string
{
    case ATTACHED = 'attached';

    case UNATTACHED = 'unattached';
}
