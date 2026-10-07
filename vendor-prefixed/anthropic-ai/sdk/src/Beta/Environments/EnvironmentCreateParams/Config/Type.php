<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Environments\EnvironmentCreateParams\Config;

enum Type: string
{
    case CLOUD = 'cloud';

    case SELF_HOSTED = 'self_hosted';
}
