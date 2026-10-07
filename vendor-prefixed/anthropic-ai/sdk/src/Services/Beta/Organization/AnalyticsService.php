<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization;

use MindioMagicAI\Vendor\Anthropic\Client;
use MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Organization\AnalyticsContract;
use MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization\Analytics\AppsService;
use MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization\Analytics\ArtifactsService;
use MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization\Analytics\ConnectorsService;
use MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization\Analytics\CostReportService;
use MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization\Analytics\PluginsService;
use MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization\Analytics\SkillsService;
use MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization\Analytics\SummariesService;
use MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization\Analytics\UsageReportService;
use MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization\Analytics\UserCostReportService;
use MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization\Analytics\UsersService;
use MindioMagicAI\Vendor\Anthropic\Services\Beta\Organization\Analytics\UserUsageReportService;

final class AnalyticsService implements AnalyticsContract
{
    /**
     * @api
     */
    public AnalyticsRawService $raw;

    /**
     * @api
     */
    public SummariesService $summaries;

    /**
     * @api
     */
    public UsersService $users;

    /**
     * @api
     */
    public AppsService $apps;

    /**
     * @api
     */
    public ConnectorsService $connectors;

    /**
     * @api
     */
    public PluginsService $plugins;

    /**
     * @api
     */
    public SkillsService $skills;

    /**
     * @api
     */
    public ArtifactsService $artifacts;

    /**
     * @api
     */
    public UsageReportService $usageReport;

    /**
     * @api
     */
    public UserUsageReportService $userUsageReport;

    /**
     * @api
     */
    public CostReportService $costReport;

    /**
     * @api
     */
    public UserCostReportService $userCostReport;

    /**
     * @internal
     */
    public function __construct(private Client $client)
    {
        $this->raw = new AnalyticsRawService($client);
        $this->summaries = new SummariesService($client);
        $this->users = new UsersService($client);
        $this->apps = new AppsService($client);
        $this->connectors = new ConnectorsService($client);
        $this->plugins = new PluginsService($client);
        $this->skills = new SkillsService($client);
        $this->artifacts = new ArtifactsService($client);
        $this->usageReport = new UsageReportService($client);
        $this->userUsageReport = new UserUsageReportService($client);
        $this->costReport = new CostReportService($client);
        $this->userCostReport = new UserCostReportService($client);
    }
}
