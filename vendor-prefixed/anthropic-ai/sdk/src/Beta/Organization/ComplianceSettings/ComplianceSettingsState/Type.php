<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\ComplianceSettings\ComplianceSettingsState;

enum Type: string
{
    case ENABLED = 'enabled';

    case DISABLED = 'disabled';
}
