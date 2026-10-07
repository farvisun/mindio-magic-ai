<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Messages;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkUnion;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\Converter;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion\Contracts\ConverterSource;

/**
 * Container identifier for reuse across requests.
 *
 * @phpstan-import-type ContainerParamsShape from \Anthropic\Messages\ContainerParams
 *
 * @phpstan-type MessageCreateParamsContainerVariants = string|ContainerParams
 * @phpstan-type MessageCreateParamsContainerShape = MessageCreateParamsContainerVariants|ContainerParamsShape
 */
final class MessageCreateParamsContainer implements ConverterSource
{
    use SdkUnion;

    /**
     * @return list<string|Converter|ConverterSource>|array<string,string|Converter|ConverterSource>
     */
    public static function variants(): array
    {
        return [ContainerParams::class, 'string'];
    }
}
