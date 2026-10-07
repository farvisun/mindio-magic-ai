<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Services\Beta\Sessions\Threads;

use MindioMagicAI\Vendor\Anthropic\Beta\AnthropicBeta;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsDeltaEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsDeltaType;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsSessionUpdatedEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsSessionUsageEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsStartEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsSystemMessageEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsUserToolResultEvent;
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
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionDeletedEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionErrorEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionEvent;
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
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Threads\Events\EventListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Threads\Events\EventStreamParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Threads\ManagedAgentsStreamSessionThreadEvents;
use MindioMagicAI\Vendor\Anthropic\Client;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseStream;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\Core\Util;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;
use MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Sessions\Threads\EventsRawContract;
use MindioMagicAI\Vendor\Anthropic\SSEStream;

/**
 * @phpstan-import-type RequestOpts from \Anthropic\RequestOptions
 */
final class EventsRawService implements EventsRawContract
{
    // @phpstan-ignore-next-line
    /**
     * @internal
     */
    public function __construct(private Client $client) {}

    /**
     * @api
     *
     * List Session Thread Events
     *
     * @param string $threadID Path param
     * @param array{
     *   sessionID: string,
     *   limit?: int,
     *   page?: string,
     *   betas?: list<string|AnthropicBeta|value-of<AnthropicBeta>>,
     *   workspaceID?: string,
     * }|EventListParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<PageCursor<ManagedAgentsUserMessageEvent|ManagedAgentsUserInterruptEvent|ManagedAgentsUserToolConfirmationEvent|ManagedAgentsUserCustomToolResultEvent|ManagedAgentsAgentCustomToolUseEvent|ManagedAgentsAgentMessageEvent|ManagedAgentsAgentThinkingEvent|ManagedAgentsAgentMCPToolUseEvent|ManagedAgentsAgentMCPToolResultEvent|ManagedAgentsAgentToolUseEvent|ManagedAgentsAgentToolResultEvent|ManagedAgentsAgentThreadMessageReceivedEvent|ManagedAgentsAgentThreadMessageSentEvent|ManagedAgentsAgentThreadContextCompactedEvent|ManagedAgentsSessionErrorEvent|ManagedAgentsSessionStatusRescheduledEvent|ManagedAgentsSessionStatusRunningEvent|ManagedAgentsSessionStatusIdleEvent|ManagedAgentsSessionStatusTerminatedEvent|ManagedAgentsSessionThreadCreatedEvent|ManagedAgentsSpanOutcomeEvaluationStartEvent|ManagedAgentsSpanOutcomeEvaluationEndEvent|ManagedAgentsSpanModelRequestStartEvent|ManagedAgentsSpanModelRequestEndEvent|ManagedAgentsSpanOutcomeEvaluationOngoingEvent|ManagedAgentsUserDefineOutcomeEvent|ManagedAgentsSessionDeletedEvent|ManagedAgentsSessionThreadStatusRunningEvent|ManagedAgentsSessionThreadStatusIdleEvent|ManagedAgentsSessionThreadStatusTerminatedEvent|BetaManagedAgentsUserToolResultEvent|ManagedAgentsSessionThreadStatusRescheduledEvent|BetaManagedAgentsSessionUpdatedEvent|BetaManagedAgentsSystemMessageEvent|BetaManagedAgentsSessionUsageEvent,>,>
     *
     * @throws APIException
     */
    public function list(
        string $threadID,
        array|EventListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse {
        [$parsed, $options] = EventListParams::parseRequest(
            $params,
            $requestOptions,
        );
        $sessionID = $parsed['sessionID'];
        unset($parsed['sessionID']);
        $query_params = array_flip(['limit', 'page']);

        /** @var array<string,string> */
        $header_params = array_diff_key($parsed, $query_params);

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'get',
            path: [
                'v1/sessions/%1$s/threads/%2$s/events?beta=true', $sessionID, $threadID,
            ],
            query: array_intersect_key($parsed, $query_params),
            headers: Util::array_transform_keys(
                $header_params,
                [
                    'betas' => 'anthropic-beta', 'workspaceID' => 'anthropic-workspace-id',
                ],
            ),
            options: RequestOptions::parse(
                ['extraHeaders' => ['anthropic-beta' => 'managed-agents-2026-04-01']],
                $options,
            ),
            convert: ManagedAgentsSessionEvent::class,
            page: PageCursor::class,
        );
    }

    /**
     * @api
     *
     * @param string $threadID Path param
     * @param array{
     *   sessionID: string,
     *   eventDeltas?: list<BetaManagedAgentsDeltaType|value-of<BetaManagedAgentsDeltaType>>,
     *   betas?: list<string|AnthropicBeta|value-of<AnthropicBeta>>,
     *   workspaceID?: string,
     * }|EventStreamParams $params
     * @param RequestOpts|null $requestOptions
     *
     * @return BaseResponse<BaseStream<ManagedAgentsUserMessageEvent|ManagedAgentsUserInterruptEvent|ManagedAgentsUserToolConfirmationEvent|ManagedAgentsUserCustomToolResultEvent|ManagedAgentsAgentCustomToolUseEvent|ManagedAgentsAgentMessageEvent|ManagedAgentsAgentThinkingEvent|ManagedAgentsAgentMCPToolUseEvent|ManagedAgentsAgentMCPToolResultEvent|ManagedAgentsAgentToolUseEvent|ManagedAgentsAgentToolResultEvent|ManagedAgentsAgentThreadMessageReceivedEvent|ManagedAgentsAgentThreadMessageSentEvent|ManagedAgentsAgentThreadContextCompactedEvent|ManagedAgentsSessionErrorEvent|ManagedAgentsSessionStatusRescheduledEvent|ManagedAgentsSessionStatusRunningEvent|ManagedAgentsSessionStatusIdleEvent|ManagedAgentsSessionStatusTerminatedEvent|ManagedAgentsSessionThreadCreatedEvent|ManagedAgentsSpanOutcomeEvaluationStartEvent|ManagedAgentsSpanOutcomeEvaluationEndEvent|ManagedAgentsSpanModelRequestStartEvent|ManagedAgentsSpanModelRequestEndEvent|ManagedAgentsSpanOutcomeEvaluationOngoingEvent|ManagedAgentsUserDefineOutcomeEvent|ManagedAgentsSessionDeletedEvent|ManagedAgentsSessionThreadStatusRunningEvent|ManagedAgentsSessionThreadStatusIdleEvent|ManagedAgentsSessionThreadStatusTerminatedEvent|BetaManagedAgentsUserToolResultEvent|ManagedAgentsSessionThreadStatusRescheduledEvent|BetaManagedAgentsSessionUpdatedEvent|BetaManagedAgentsStartEvent|BetaManagedAgentsDeltaEvent|BetaManagedAgentsSystemMessageEvent|BetaManagedAgentsSessionUsageEvent,>,>
     *
     * @throws APIException
     */
    public function streamStream(
        string $threadID,
        array|EventStreamParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse {
        [$parsed, $options] = EventStreamParams::parseRequest(
            $params,
            $requestOptions,
        );
        $sessionID = $parsed['sessionID'];
        unset($parsed['sessionID']);
        $query_params = array_flip(['eventDeltas']);

        /** @var array<string,string> */
        $header_params = array_diff_key($parsed, $query_params);

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'get',
            path: [
                'v1/sessions/%1$s/threads/%2$s/stream?beta=true', $sessionID, $threadID,
            ],
            query: Util::array_transform_keys(
                array_intersect_key($parsed, $query_params),
                ['eventDeltas' => 'event_deltas'],
            ),
            headers: Util::array_transform_keys(
                ['Accept' => 'text/event-stream', ...$header_params],
                [
                    'betas' => 'anthropic-beta', 'workspaceID' => 'anthropic-workspace-id',
                ],
            ),
            options: RequestOptions::parse(
                ['extraHeaders' => ['anthropic-beta' => 'managed-agents-2026-04-01']],
                $options,
            ),
            convert: ManagedAgentsStreamSessionThreadEvents::class,
            stream: SSEStream::class,
        );
    }
}
