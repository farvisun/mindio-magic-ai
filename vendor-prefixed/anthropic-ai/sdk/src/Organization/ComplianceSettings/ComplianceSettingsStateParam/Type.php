<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Organization\ComplianceSettings\ComplianceSettingsStateParam;

enum Type: string
{
    case ENABLED = 'enabled';

    case DISABLED = 'disabled';
}
