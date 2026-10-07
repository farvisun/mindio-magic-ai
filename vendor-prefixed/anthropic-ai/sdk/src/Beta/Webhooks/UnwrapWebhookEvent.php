<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Webhooks;

// @deprecated UnwrapWebhookEvent has been renamed to BetaWebhookEvent
class_alias(
    BetaWebhookEvent::class,
    'MindioMagicAI\Vendor\Anthropic\Beta\Webhooks\UnwrapWebhookEvent'
);
