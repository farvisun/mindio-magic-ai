<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaAdvisorToolResultBlockParam;

use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaAdvisorRedactedResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaAdvisorResultBlockParam;
use MindioMagicAI\Vendor\Anthropic\Beta\Messages\BetaAdvisorToolResultErrorParam;
use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * @phpstan-import-type BetaAdvisorToolResultErrorParamShape from \Anthropic\Beta\Messages\BetaAdvisorToolResultErrorParam
 * @phpstan-import-type BetaAdvisorResultBlockParamShape from \Anthropic\Beta\Messages\BetaAdvisorResultBlockParam
 * @phpstan-import-type BetaAdvisorRedactedResultBlockParamShape from \Anthropic\Beta\Messages\BetaAdvisorRedactedResultBlockParam
 *
 * @phpstan-type ContentVariants = BetaAdvisorToolResultErrorParam|BetaAdvisorResultBlockParam|BetaAdvisorRedactedResultBlockParam
 * @phpstan-type ContentShape = ContentVariants|BetaAdvisorToolResultErrorParamShape|BetaAdvisorResultBlockParamShape|BetaAdvisorRedactedResultBlockParamShape
 */
final class Content implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [
            BetaAdvisorToolResultErrorParam::class,
            BetaAdvisorResultBlockParam::class,
            BetaAdvisorRedactedResultBlockParam::class,
        ];
    }
}
