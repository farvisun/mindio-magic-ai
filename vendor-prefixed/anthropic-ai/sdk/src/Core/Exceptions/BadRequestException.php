<?php

namespace MindioMagicAI\Vendor\Anthropic\Core\Exceptions;

class BadRequestException extends APIStatusException
{
    /** @var string */
    protected const DESC = 'Anthropic Bad Request Exception';
}
