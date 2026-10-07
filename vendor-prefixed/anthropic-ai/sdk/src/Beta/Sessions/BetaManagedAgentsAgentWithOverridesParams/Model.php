<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsAgentWithOverridesParams;

use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsModel;
use MindioMagicAI\Vendor\Anthropic\Beta\Agents\BetaManagedAgentsModelConfigParams;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * Replacement model. Accepts the model string, e.g. `claude-opus-5`, or a `model_config` object. Omit to use the agent's model.
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
