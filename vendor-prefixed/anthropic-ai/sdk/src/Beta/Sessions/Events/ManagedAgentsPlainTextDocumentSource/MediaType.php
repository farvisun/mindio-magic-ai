<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsPlainTextDocumentSource;

/**
 * MIME type of the text content. Must be "text/plain".
 */
enum MediaType: string
{
    case TEXT_PLAIN = 'text/plain';
}
