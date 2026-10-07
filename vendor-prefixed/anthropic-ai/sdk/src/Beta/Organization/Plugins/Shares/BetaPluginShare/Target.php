<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\Plugins\Shares\BetaPluginShare;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Plugins\PluginTargetOrganization;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Plugins\PluginTargetOrganizationMember;
use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Plugins\PluginTargetRBACGroup;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * Who the Plugin is shared with: `organization` (every member), `rbac_group` (one RBAC Group), or `organization_member` (one member).
 *
 * @phpstan-import-type PluginTargetOrganizationShape from \Anthropic\Beta\Organization\Plugins\PluginTargetOrganization
 * @phpstan-import-type PluginTargetRBACGroupShape from \Anthropic\Beta\Organization\Plugins\PluginTargetRBACGroup
 * @phpstan-import-type PluginTargetOrganizationMemberShape from \Anthropic\Beta\Organization\Plugins\PluginTargetOrganizationMember
 *
 * @phpstan-type TargetVariants = PluginTargetOrganization|PluginTargetRBACGroup|PluginTargetOrganizationMember
 * @phpstan-type TargetShape = TargetVariants|PluginTargetOrganizationShape|PluginTargetRBACGroupShape|PluginTargetOrganizationMemberShape
 */
final class Target implements ConverterSource
{
    use SdkUnion;

    public static function discriminator(): string
    {
        return 'type';
    }

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [
            'organization' => PluginTargetOrganization::class,
            'rbac_group' => PluginTargetRBACGroup::class,
            'organization_member' => PluginTargetOrganizationMember::class,
        ];
    }
}
