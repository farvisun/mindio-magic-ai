<?php

namespace MindioMagicAI\Vendor\Anthropic;

use MindioMagicAI\Vendor\Anthropic\Core\Concerns\SdkStream;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseStream;
use MindioMagicAI\Vendor\Anthropic\Core\Conversion;

/**
 * @template TItem
 *
 * @implements BaseStream<TItem>
 */
final class JsonLStream implements BaseStream
{
    /** @use SdkStream<mixed, TItem> */
    use SdkStream;

    private function parsedGenerator(): \Generator
    {
        if (!$this->stream->valid()) {
            return;
        }

        foreach ($this->stream as $row) {
            yield Conversion::coerce($this->convert, value: $row);
        }
    }
}
