<?php

namespace MindioMagicAI\Vendor\Anthropic\Core\Exceptions;

class PermissionDeniedException extends APIStatusException
{
    /** @var string */
    protected const DESC = 'Anthropic Permission Denied Exception';
}
