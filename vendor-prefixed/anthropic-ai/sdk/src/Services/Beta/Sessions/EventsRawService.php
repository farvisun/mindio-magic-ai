<?php

declare(strict_types=1);

namespace MindioMagicAI\Vendor\Anthropic\Services\Beta\Sessions;

use MindioMagicAI\Vendor\Anthropic\Beta\AnthropicBeta;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsDeltaEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsDeltaType;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsSessionUpdatedEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsSessionUsageEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsStartEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsSystemMessageEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\BetaManagedAgentsUserToolResultEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\EventListParams;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\EventListParams\Order;
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
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsSessionEventType;
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
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsStreamSessionEvents;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsUserCustomToolResultEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsUserDefineOutcomeEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsUserInterruptEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsUserMessageEvent;
use MindioMagicAI\Vendor\Anthropic\Beta\Sessions\Events\ManagedAgentsUserToolConfirmationEvent;
use MindioMagicAI\Vendor\Anthropic\Client;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseResponse;
use MindioMagicAI\Vendor\Anthropic\Core\Contracts\BaseStream;
use MindioMagicAI\Vendor\Anthropic\Core\Exceptions\APIException;
use MindioMagicAI\Vendor\Anthropic\Core\Util;
use MindioMagicAI\Vendor\Anthropic\PageCursor;
use MindioMagicAI\Vendor\Anthropic\RequestOptions;
use MindioMagicAI\Vendor\Anthropic\ServiceContracts\Beta\Sessions\EventsRawContract;
use MindioMagicAI\Vendor\Anthropic\SSEStream;

/**
 * @phpstan-import-type ManagedAgentsEventParamsShape from \Anthropic\Beta\Sessions\Events\ManagedAgentsEventParams
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
     * List Events
     *
     * @param string $sessionID Path param
     * @param array{
     *   createdAtGt?: \DateTimeInterface,
     *   createdAtGte?: \DateTimeInterface,
     *   createdAtLt?: \DateTimeInterface,
     *   createdAtLte?: \DateTimeInterface,
     *   limit?: int,
     *   order?: Order|value-of<Order>,
     *   page?: string,
     *   types?: list<ManagedAgentsSessionEventType|value-of<ManagedAgentsSessionEventType>>,
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
        string $sessionID,
        array|EventListParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse {
        [$parsed, $options] = EventListParams::parseRequest(
            $params,
            $requestOptions,
        );
        $query_params = array_flip(
            [
                'createdAtGt',
                'createdAtGte',
                'createdAtLt',
                'createdAtLte',
                'limit',
                'order',
                'page',
                'types',
            ],
        );

        /** @var array<string,string> */
        $header_params = array_diff_key($parsed, $query_params);

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'get',
            path: ['v1/sessions/%1$s/events?beta=true', $sessionID],
            query: Util::array_transform_keys(
                array_intersect_key($parsed, $query_params),
                [
                    'createdAtGt' => 'created_at[gt]',
                    'createdAtGte' => 'created_at[gte]',
                    'createdAtLt' => 'created_at[lt]',
                    'createdAtLte' => 'created_at[lte]',
                ],
            ),
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
     * Send Events
     *
     * @param string $sessionID Path param
     * @param array{
     *   events: list<ManagedAgentsEventParamsShape>,
     *   betas?: list<string|AnthropicBeta|value-of<AnthropicBeta>>,
     *   workspaceID?: string,
     * }|EventSendParams $params
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
    ): BaseResponse {
        [$parsed, $options] = EventSendParams::parseRequest(
            $params,
            $requestOptions,
        );
        $header_params = [
            'betas' => 'anthropic-beta', 'workspaceID' => 'anthropic-workspace-id',
        ];

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'post',
            path: ['v1/sessions/%1$s/events?beta=true', $sessionID],
            headers: Util::array_transform_keys(
                array_intersect_key($parsed, array_flip(array_keys($header_params))),
                $header_params,
            ),
            body: (object) array_diff_key(
                $parsed,
                array_flip(array_keys($header_params))
            ),
            options: RequestOptions::parse(
                ['extraHeaders' => ['anthropic-beta' => 'managed-agents-2026-04-01']],
                $options,
            ),
            convert: ManagedAgentsSendSessionEvents::class,
        );
    }

    /**
     * @api
     *
     * @param string $sessionID Path param
     * @param array{
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
        string $sessionID,
        array|EventStreamParams $params,
        RequestOptions|array|null $requestOptions = null,
    ): BaseResponse {
        [$parsed, $options] = EventStreamParams::parseRequest(
            $params,
            $requestOptions,
        );
        $query_params = array_flip(['eventDeltas']);

        /** @var array<string,string> */
        $header_params = array_diff_key($parsed, $query_params);

        // @phpstan-ignore-next-line return.type
        return $this->client->request(
            method: 'get',
            path: ['v1/sessions/%1$s/events/stream?beta=true', $sessionID],
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
            convert: ManagedAgentsStreamSessionEvents::class,
            stream: SSEStream::class,
        );
    }
}
