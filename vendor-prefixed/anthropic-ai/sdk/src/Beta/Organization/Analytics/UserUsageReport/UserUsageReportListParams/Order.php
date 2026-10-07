<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\Analytics\UserUsageReport\UserUsageReportListParams;

/**
 * Sort direction. Defaults to `desc`.
 */
enum Order: string
{
    case ASC = 'asc';

    case DESC = 'desc';
}
