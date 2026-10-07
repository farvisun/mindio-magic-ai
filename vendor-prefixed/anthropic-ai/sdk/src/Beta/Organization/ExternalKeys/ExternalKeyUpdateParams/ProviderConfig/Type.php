<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\ExternalKeys\ExternalKeyUpdateParams\ProviderConfig;

enum Type: string
{
    case AWS = 'aws';

    case GCP = 'gcp';

    case AZURE = 'azure';
}
