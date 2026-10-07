<?php

namespace MindioMagicAI\Vendor\Anthropic\Core\Exceptions;

class ConflictException extends APIStatusException
{
    /** @var string */
    protected const DESC = 'Anthropic Conflict Exception';
}
