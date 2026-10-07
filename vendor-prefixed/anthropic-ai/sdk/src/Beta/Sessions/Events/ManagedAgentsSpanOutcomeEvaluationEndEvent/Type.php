<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSpanOutcomeEvaluationEndEvent;

enum Type: string
{
    case SPAN_OUTCOME_EVALUATION_END = 'span.outcome_evaluation_end';
}
