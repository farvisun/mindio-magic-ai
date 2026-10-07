<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Sessions;

use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsDeltaEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsSessionUpdatedEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsSessionUsageEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsStartEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsSystemMessageEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsUserToolResultEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\EventListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\EventSendParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\EventStreamParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsAgentCustomToolUseEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsAgentMCPToolResultEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsAgentMCPToolUseEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsAgentMessageEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsAgentThinkingEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsAgentThreadContextCompactedEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsAgentThreadMessageReceivedEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsAgentThreadMessageSentEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsAgentToolResultEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsAgentToolUseEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSendSessionEvents;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionDeletedEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionErrorEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionStatusIdleEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionStatusRescheduledEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionStatusRunningEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionStatusTerminatedEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionThreadCreatedEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionThreadStatusIdleEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionThreadStatusRescheduledEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionThreadStatusRunningEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionThreadStatusTerminatedEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSpanModelRequestEndEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSpanModelRequestStartEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSpanOutcomeEvaluationEndEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSpanOutcomeEvaluationOngoingEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSpanOutcomeEvaluationStartEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsUserCustomToolResultEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsUserDefineOutcomeEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsUserInterruptEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsUserMessageEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsUserToolConfirmationEvent;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseStream;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
interface EventsRawContract
{
    /**
     * @api
     *
     * @param string $sessionID Path param
     * @param array<string,mixed>|EventListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<ManagedAgentsUserMessageEvent|ManagedAgentsUserInterruptEvent|ManagedAgentsUserToolConfirmationEvent|ManagedAgentsUserCustomToolResultEvent|ManagedAgentsAgentCustomToolUseEvent|ManagedAgentsAgentMessageEvent|ManagedAgentsAgentThinkingEvent|ManagedAgentsAgentMCPToolUseEvent|ManagedAgentsAgentMCPToolResultEvent|ManagedAgentsAgentToolUseEvent|ManagedAgentsAgentToolResultEvent|ManagedAgentsAgentThreadMessageReceivedEvent|ManagedAgentsAgentThreadMessageSentEvent|ManagedAgentsAgentThreadContextCompactedEvent|ManagedAgentsSessionErrorEvent|ManagedAgentsSessionStatusRescheduledEvent|ManagedAgentsSessionStatusRunningEvent|ManagedAgentsSessionStatusIdleEvent|ManagedAgentsSessionStatusTerminatedEvent|ManagedAgentsSessionThreadCreatedEvent|ManagedAgentsSpanOutcomeEvaluationStartEvent|ManagedAgentsSpanOutcomeEvaluationEndEvent|ManagedAgentsSpanModelRequestStartEvent|ManagedAgentsSpanModelRequestEndEvent|ManagedAgentsSpanOutcomeEvaluationOngoingEvent|ManagedAgentsUserDefineOutcomeEvent|ManagedAgentsSessionDeletedEvent|ManagedAgentsSessionThreadStatusRunningEvent|ManagedAgentsSessionThreadStatusIdleEvent|ManagedAgentsSessionThreadStatusTerminatedEvent|BetaManagedAgentsUserToolResultEvent|ManagedAgentsSessionThreadStatusRescheduledEvent|BetaManagedAgentsSessionUpdatedEvent|BetaManagedAgentsSystemMessageEvent|BetaManagedAgentsSessionUsageEvent,>,>
     *
     * @throws APIException
     */
    public function list(
        string $sessionID,
        array|EventListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $sessionID Path param
     * @param array<string,mixed>|EventSendParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<ManagedAgentsSendSessionEvents>
     *
     * @throws APIException
     */
    public function send(
        string $sessionID,
        array|EventSendParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;

    /**
     * @api
     *
     * @param string $sessionID Path param
     * @param array<string,mixed>|EventStreamParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BaseStream<ManagedAgentsUserMessageEvent|ManagedAgentsUserInterruptEvent|ManagedAgentsUserToolConfirmationEvent|ManagedAgentsUserCustomToolResultEvent|ManagedAgentsAgentCustomToolUseEvent|ManagedAgentsAgentMessageEvent|ManagedAgentsAgentThinkingEvent|ManagedAgentsAgentMCPToolUseEvent|ManagedAgentsAgentMCPToolResultEvent|ManagedAgentsAgentToolUseEvent|ManagedAgentsAgentToolResultEvent|ManagedAgentsAgentThreadMessageReceivedEvent|ManagedAgentsAgentThreadMessageSentEvent|ManagedAgentsAgentThreadContextCompactedEvent|ManagedAgentsSessionErrorEvent|ManagedAgentsSessionStatusRescheduledEvent|ManagedAgentsSessionStatusRunningEvent|ManagedAgentsSessionStatusIdleEvent|ManagedAgentsSessionStatusTerminatedEvent|ManagedAgentsSessionThreadCreatedEvent|ManagedAgentsSpanOutcomeEvaluationStartEvent|ManagedAgentsSpanOutcomeEvaluationEndEvent|ManagedAgentsSpanModelRequestStartEvent|ManagedAgentsSpanModelRequestEndEvent|ManagedAgentsSpanOutcomeEvaluationOngoingEvent|ManagedAgentsUserDefineOutcomeEvent|ManagedAgentsSessionDeletedEvent|ManagedAgentsSessionThreadStatusRunningEvent|ManagedAgentsSessionThreadStatusIdleEvent|ManagedAgentsSessionThreadStatusTerminatedEvent|BetaManagedAgentsUserToolResultEvent|ManagedAgentsSessionThreadStatusRescheduledEvent|BetaManagedAgentsSessionUpdatedEvent|BetaManagedAgentsStartEvent|BetaManagedAgentsDeltaEvent|BetaManagedAgentsSystemMessageEvent|BetaManagedAgentsSessionUsageEvent,>,>
     *
     * @throws APIException
     */
    public function streamStream(
        string $sessionID,
        array|EventStreamParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse;
}
