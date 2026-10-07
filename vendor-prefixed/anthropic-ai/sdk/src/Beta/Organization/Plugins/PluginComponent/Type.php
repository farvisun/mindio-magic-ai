<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\Plugins\PluginComponent;

/**
 * The kind of component.
 */
enum Type: string
{
    case AGENT = 'agent';

    case CLI = 'cli';

    case COMMAND = 'command';

    case HOOK = 'hook';

    case MCP_SERVER = 'mcp_server';

    case SKILL = 'skill';
}
