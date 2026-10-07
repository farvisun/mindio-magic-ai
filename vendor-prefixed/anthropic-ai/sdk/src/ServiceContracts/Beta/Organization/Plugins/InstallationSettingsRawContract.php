<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Organization\Plugins;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Plugins\InstallationSettings\BetaDeletedPluginInstallationSetting;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Plugins\InstallationSettings\BetaPluginInstallationSetting;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Plugins\InstallationSettings\InstallationSettingListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Plugins\InstallationSettings\InstallationSettingRemoveParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Plugins\InstallationSettings\InstallationSettingSetParams;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface InstallationSettingsRawContract
{
    /**
     * @api
     *
     * @param string $pluginID path param: ID of the Plugin (prefixed `plugin_`)
     * @param array<string,mixed>|InstallationSettingListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<BetaPluginInstallationSetting>>
     *
     * @throws APIException
     */
    public function list(
        string $pluginID,
        array|InstallationSettingListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $target Path param: The target whose own setting is removed: the literal `organization` for the Plugin's organization-wide setting, or an RBAC Group's ID (prefixed `rbac_group_`) for that group's own setting. Removing the `organization` setting returns the Plugin to its marketplace's default.
     * @param array<string,mixed>|InstallationSettingRemoveParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaDeletedPluginInstallationSetting>
     *
     * @throws APIException
     */
    public function remove(
        string $target,
        array|InstallationSettingRemoveParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $target Path param: The target whose setting is written: the literal `organization` for the Plugin's organization-wide setting, or an RBAC Group's ID (prefixed `rbac_group_`) for that group's own setting. Writing the `organization` target stops the Plugin from inheriting its marketplace's default, even when the value written equals that default.
     * @param array<string,mixed>|InstallationSettingSetParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BetaPluginInstallationSetting>
     *
     * @throws APIException
     */
    public function set(
        string $target,
        array|InstallationSettingSetParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
