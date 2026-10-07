<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\Federation\Issuers\BetaFederationIssuer\JWKS;

enum Type: string
{
    case DISCOVERY = 'discovery';

    case EXPLICIT_URL = 'explicit_url';

    case INLINE = 'inline';
}
