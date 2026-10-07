<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRequestToolRemovalBlock;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaRequestToolRemovalBlock\Tool\Type;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaToolChangeMCPToolReference;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaToolChangeMCPToolsetReference;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaToolChangeToolReference;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type BetaToolChangeToolReferenceShape from \Anthropic\Beta\Messages\BetaToolChangeToolReference
 * @phpstan-import-type BetaToolChangeMCPToolReferenceShape from \Anthropic\Beta\Messages\BetaToolChangeMCPToolReference
 * @phpstan-import-type BetaToolChangeMCPToolsetReferenceShape from \Anthropic\Beta\Messages\BetaToolChangeMCPToolsetReference
 *
 * @phpstan-type ToolVariants = BetaToolChangeToolReference|BetaToolChangeMCPToolReference|BetaToolChangeMCPToolsetReference
 * @phpstan-type ToolShape = ToolVariants|BetaToolChangeToolReferenceShape|BetaToolChangeMCPToolReferenceShape|BetaToolChangeMCPToolsetReferenceShape
 */
final class Tool implements ConverterSource
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
            'tool_reference' => BetaToolChangeToolReference::class,
            'mcp_tool_reference' => BetaToolChangeMCPToolReference::class,
            'mcp_toolset_reference' => BetaToolChangeMCPToolsetReference::class,
        ];
    }

    /**
     * Constructs the variant whose `type` matches the given value, forwarding the remaining arguments to its own `with()`.
     *
     * @return ($type is Type::TOOL_REFERENCE|'tool_reference' ? BetaToolChangeToolReference : ($type is Type::MCP_TOOL_REFERENCE|'mcp_tool_reference' ? BetaToolChangeMCPToolReference : ($type is Type::MCP_TOOLSET_REFERENCE|'mcp_toolset_reference' ? BetaToolChangeMCPToolsetReference : BetaToolChangeToolReference|BetaToolChangeMCPToolReference|BetaToolChangeMCPToolsetReference)))
     *
     * @throws \UnhandledMatchError
     */
    public static function with(
        Type|string $type,
        ?string $name = null,
        ?string $serverName = null
    ): BetaToolChangeToolReference|BetaToolChangeMCPToolReference|BetaToolChangeMCPToolsetReference {
        return match ($type) {
            Type::TOOL_REFERENCE, 'tool_reference' => BetaToolChangeToolReference::with(
                name: $name ?? throw new \ArgumentCountError('$name is required')
            ),
            Type::MCP_TOOL_REFERENCE, 'mcp_tool_reference' => BetaToolChangeMCPToolReference::with(
                name: $name ?? throw new \ArgumentCountError('$name is required'),
                serverName: $serverName ?? throw new \ArgumentCountError('$serverName is required'),
            ),
            Type::MCP_TOOLSET_REFERENCE, 'mcp_toolset_reference' => BetaToolChangeMCPToolsetReference::with(
                serverName: $serverName ?? throw new \ArgumentCountError('$serverName is required'),
            ),
            default => throw new \UnhandledMatchError(sprintf('Unhandled match case %s', var_export($type, true)))
        };
    }
}
