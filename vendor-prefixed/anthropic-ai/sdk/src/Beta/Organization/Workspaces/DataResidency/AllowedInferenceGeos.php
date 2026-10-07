<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Organization\Workspaces\DataResidency;

use MindioMagicAI\Vendor\Anthropic\Beta\Organization\Workspaces\AllowedInferenceGeo;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ConstantOf;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ListOf;

/**
 * Permitted inference geo values. 'unrestricted' means all geos are allowed.
 *
 * @phpstan-type AllowedInferenceGeosVariants = 'unrestricted'|list<value-of<AllowedInferenceGeo>>
 * @phpstan-type AllowedInferenceGeosShape = AllowedInferenceGeosVariants
 */
final class AllowedInferenceGeos implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [
            new ListOf(AllowedInferenceGeo::class), new ConstantOf('unrestricted'),
        ];
    }
}
