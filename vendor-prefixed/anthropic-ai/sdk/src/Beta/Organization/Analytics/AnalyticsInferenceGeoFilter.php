<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\Analytics;

enum AnalyticsInferenceGeoFilter: string
{
    case GLOBAL = 'global';

    case NOT_AVAILABLE = 'not_available';

    case US = 'us';
}
