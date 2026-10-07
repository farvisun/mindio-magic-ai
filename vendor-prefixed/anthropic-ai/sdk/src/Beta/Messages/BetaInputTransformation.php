<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * One entry of `input_transformations`: either a change the API made to the
 * request's input before showing it to the model, or a block that failed a
 * binding check and was still shown to the model unchanged. The `type` field
 * says which.
 *
 * @phpstan-import-type BetaThinkingDroppedInputTransformationShape from \Anthropic\Beta\Messages\BetaThinkingDroppedInputTransformation
 * @phpstan-import-type BetaThinkingMismatchAllowedInputTransformationShape from \Anthropic\Beta\Messages\BetaThinkingMismatchAllowedInputTransformation
 *
 * @phpstan-type BetaInputTransformationVariants = BetaThinkingDroppedInputTransformation|BetaThinkingMismatchAllowedInputTransformation
 * @phpstan-type BetaInputTransformationShape = BetaInputTransformationVariants|BetaThinkingDroppedInputTransformationShape|BetaThinkingMismatchAllowedInputTransformationShape
 */
final class BetaInputTransformation implements ConverterSource
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
            'thinking_dropped' => BetaThinkingDroppedInputTransformation::class,
            'thinking_mismatch_allowed' => BetaThinkingMismatchAllowedInputTransformation::class,
        ];
    }
}
