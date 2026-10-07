<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Vaults\Credentials\ManagedAgentsEnvironmentVariableAuthResponse\Networking;

enum Type: string
{
    case UNRESTRICTED = 'unrestricted';

    case LIMITED = 'limited';
}
