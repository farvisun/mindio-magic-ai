<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsSessionThreadAgent\Skill;

enum Type: string
{
    case ANTHROPIC = 'anthropic';

    case CUSTOM = 'custom';
}
