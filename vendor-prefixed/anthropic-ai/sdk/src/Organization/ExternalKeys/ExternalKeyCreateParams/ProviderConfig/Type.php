<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Organization\ExternalKeys\ExternalKeyCreateParams\ProviderConfig;

enum Type: string
{
    case AWS = 'aws';

    case GCP = 'gcp';

    case AZURE = 'azure';
}
