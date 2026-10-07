<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Vaults\Credentials\ManagedAgentsCredentialNetworkingParams;

enum Type: string
{
    case UNRESTRICTED = 'unrestricted';

    case LIMITED = 'limited';
}
