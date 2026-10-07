<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Agents\AgentUpdateParams;

use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsModel;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsModelConfigParams;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * Model identifier. Accepts the [model string](https://platform.claude.com/docs/en/about-claude/models/overview#latest-models-comparison), e.g. `claude-opus-5`, or a `model_config` object for additional configuration control. Omit to preserve. Cannot be cleared.
 *
 * @phpstan-import-type BetaManagedAgentsModelConfigParamsShape from \Anthropic\Beta\Agents\BetaManagedAgentsModelConfigParams
 *
 * @phpstan-type ModelVariants = BetaManagedAgentsModelConfigParams|value-of<BetaManagedAgentsModel>
 * @phpstan-type ModelShape = ModelVariants|BetaManagedAgentsModelConfigParamsShape
 */
final class Model implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [
            BetaManagedAgentsModelConfigParams::class, BetaManagedAgentsModel::class,
        ];
    }
}
