<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\Batches\BatchCreateParams\Request\Params;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaContainerParams;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * Container identifier for reuse across requests.
 *
 * @phpstan-import-type BetaContainerParamsShape from \Anthropic\Beta\Messages\BetaContainerParams
 *
 * @phpstan-type ContainerVariants = string|BetaContainerParams
 * @phpstan-type ContainerShape = ContainerVariants|BetaContainerParamsShape
 */
final class Container implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [BetaContainerParams::class, 'string'];
    }
}
