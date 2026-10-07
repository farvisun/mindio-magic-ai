<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\DataResidencyUpdateConfig;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ConstantOf;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\ListOf;
use MindioMagicAI\Vendor\Anthropic\Organization\Workspaces\AllowedInferenceGeo;

/**
 * Permitted inference geo values. Use 'unrestricted' to allow all geos, or a list of specific geos.
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
