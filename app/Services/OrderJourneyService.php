<?php

namespace App\Services;

use App\Models\DrNetworkFlowRun;
use App\Models\Order;

class OrderJourneyService
{
    public function build(Order $order): array
    {
        $order->loadMissing('flowRun');

        $flowRun = $order->flowRun;

        if ($flowRun) {
            return $this->flowRunResponse($order, $flowRun);
        }

        if ($order->payment_status === 'failed') {
            return $this->response(
                order: $order,
                phase: 'payment',
                currentStepKey: 'payment_failed',
                isReady: false,
                journeyStatus: 'failed',
                nextAction: 'contact_support',
                message: 'We could not complete your payment. Please contact support or try again.',
                failureReason: 'payment_failed'
            );
        }

        if ($order->payment_status !== 'paid') {
            $currentStepKey = $order->stripe_checkout_id
                ? 'awaiting_payment_confirmation'
                : 'checkout';

            return $this->response(
                order: $order,
                phase: 'payment',
                currentStepKey: $currentStepKey,
                isReady: false,
                journeyStatus: $currentStepKey === 'checkout' ? 'checkout_required' : 'payment_pending',
                nextAction: $currentStepKey === 'checkout' ? 'start_checkout' : 'wait',
                nextActionKey: $currentStepKey === 'checkout' ? 'checkout' : null,
                message: $currentStepKey === 'checkout'
                    ? 'Start checkout to continue your consultation journey.'
                    : 'We are waiting for your payment confirmation.',
                retryAfterSeconds: $currentStepKey === 'checkout' ? null : 3
            );
        }

        return $this->response(
            order: $order,
            phase: 'dr_network_initialization',
            currentStepKey: null,
            isReady: false,
            journeyStatus: 'preparing',
            nextAction: 'wait',
            message: 'We are preparing your consultation journey.',
            retryAfterSeconds: 2,
            systemState: 'dr_network_initializing'
        );
    }

    private function flowRunResponse(Order $order, DrNetworkFlowRun $flowRun): array
    {
        if ($flowRun->status === DrNetworkFlowRun::STATUS_PENDING) {
            return $this->response(
                order: $order,
                phase: 'dr_network_initialization',
                currentStepKey: null,
                isReady: false,
                journeyStatus: 'preparing',
                nextAction: 'wait',
                message: 'We are preparing your consultation journey.',
                retryAfterSeconds: 2,
                systemState: 'dr_network_initializing',
                flowRun: $flowRun
            );
        }

        return match ($flowRun->status) {
            DrNetworkFlowRun::STATUS_RUNNING, DrNetworkFlowRun::STATUS_PAUSED => $this->activeWorkflowResponse($order, $flowRun),
            DrNetworkFlowRun::STATUS_COMPLETED => $this->response(
                order: $order,
                phase: 'dr_network',
                currentStepKey: 'completed',
                isReady: true,
                journeyStatus: 'completed',
                nextAction: 'complete',
                message: 'Your consultation workflow is complete.',
                flowRun: $flowRun
            ),
            DrNetworkFlowRun::STATUS_FAILED => $this->response(
                order: $order,
                phase: 'dr_network',
                currentStepKey: 'failed',
                isReady: false,
                journeyStatus: 'failed',
                nextAction: 'contact_support',
                message: 'We could not complete your consultation workflow. Please contact support.',
                flowRun: $flowRun,
                failureReason: $flowRun->failure_reason,
                failedStepKey: $flowRun->current_step_key
            ),
            DrNetworkFlowRun::STATUS_CANCELLED => $this->response(
                order: $order,
                phase: 'dr_network',
                currentStepKey: 'cancelled',
                isReady: false,
                journeyStatus: 'cancelled',
                nextAction: 'contact_support',
                message: 'This order workflow has been cancelled.',
                flowRun: $flowRun
            ),
            default => $this->response(
                order: $order,
                phase: 'dr_network_initialization',
                currentStepKey: null,
                isReady: false,
                journeyStatus: 'preparing',
                nextAction: 'wait',
                message: 'We are preparing your next steps.',
                retryAfterSeconds: 2,
                systemState: 'dr_network_initializing',
                flowRun: $flowRun
            ),
        };
    }

    private function activeWorkflowResponse(Order $order, DrNetworkFlowRun $flowRun): array
    {
        $currentStepKey = $this->journeyStepKey($flowRun);

        if (
            $order->payment_status === 'failed'
            && in_array($currentStepKey, ['checkout', 'awaiting_payment_confirmation'], true)
        ) {
            return $this->response(
                order: $order,
                phase: 'payment',
                currentStepKey: 'payment_failed',
                isReady: false,
                journeyStatus: 'failed',
                nextAction: 'contact_support',
                message: 'We could not complete your payment. Please contact support or try again.',
                failureReason: 'payment_failed'
            );
        }

        if ($currentStepKey === 'checkout') {
            return $this->response(
                order: $order,
                phase: 'payment',
                currentStepKey: 'checkout',
                isReady: false,
                journeyStatus: 'checkout_required',
                nextAction: 'start_checkout',
                nextActionKey: 'checkout',
                message: 'Start checkout to continue your consultation journey.'
            );
        }

        if ($currentStepKey === 'awaiting_payment_confirmation') {
            return $this->response(
                order: $order,
                phase: 'payment',
                currentStepKey: 'awaiting_payment_confirmation',
                isReady: false,
                journeyStatus: 'payment_pending',
                nextAction: 'wait',
                message: 'We are waiting for your payment confirmation.',
                retryAfterSeconds: 3
            );
        }

        if ($currentStepKey === null) {
            return $this->response(
                order: $order,
                phase: 'dr_network_initialization',
                currentStepKey: null,
                isReady: false,
                journeyStatus: 'preparing',
                nextAction: 'wait',
                message: 'We are preparing your consultation journey.',
                retryAfterSeconds: 2,
                systemState: 'dr_network_initializing',
                flowRun: $flowRun
            );
        }

        $awaitingReview = $currentStepKey === 'awaiting_review';

        return $this->response(
            order: $order,
            phase: 'dr_network',
            currentStepKey: $currentStepKey,
            isReady: true,
            journeyStatus: $awaitingReview ? 'awaiting_review' : 'action_required',
            nextAction: $awaitingReview ? 'wait' : 'open_workflow',
            nextActionKey: $awaitingReview ? null : 'consultation',
            message: $awaitingReview
                ? 'Your consultation has been submitted and is awaiting provider review.'
                : 'Your consultation requirements are ready.',
            retryAfterSeconds: $awaitingReview ? 30 : null,
            workflowUrl: "/api/v1/orders/{$order->order_uuid}/dr-network/current-step",
            flowRun: $flowRun
        );
    }

    private function response(
        Order $order,
        string $phase,
        ?string $currentStepKey,
        bool $isReady,
        string $journeyStatus,
        string $nextAction,
        string $message,
        ?string $nextActionKey = null,
        ?int $retryAfterSeconds = null,
        ?string $workflowUrl = null,
        ?string $systemState = null,
        ?DrNetworkFlowRun $flowRun = null,
        ?string $failureReason = null,
        ?string $failedStepKey = null
    ): array {
        $response = [
            'phase' => $phase,
            'current_step_key' => $currentStepKey,
            'is_ready' => $isReady,
            'order_status' => $this->orderStatus($order, $journeyStatus),
            'payment_status' => $order->payment_status,
            'journey_status' => $journeyStatus,
            'next_action' => $nextAction,
            'next_action_key' => $nextActionKey,
            'retry_after_seconds' => $retryAfterSeconds,
            'message' => $message,
        ];

        if ($failureReason !== null) {
            $response['failure_reason'] = $failureReason;
        }

        if ($failedStepKey !== null) {
            $response['failed_step_key'] = $failedStepKey;
        }

        if ($systemState !== null) {
            $response['system_state'] = $systemState;
        }

        if ($workflowUrl !== null) {
            $response['workflow_url'] = $workflowUrl;
        }

        if ($flowRun !== null) {
            $response['workflow'] = [
                'status' => $flowRun->status,
                'current_step_key' => $flowRun->current_step_key,
                'journey_step_key' => $currentStepKey,
                'pause_reason' => $flowRun->pause_reason,
                'failure_reason' => $flowRun->failure_reason,
            ];
        }

        return $response;
    }

    private function orderStatus(Order $order, string $journeyStatus): string
    {
        return match ($journeyStatus) {
            'completed' => 'completed',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
            default => $order->payment_status === 'failed' ? 'failed' : 'processing',
        };
    }

    private function journeyStepKey(DrNetworkFlowRun $flowRun): ?string
    {
        return match ($flowRun->current_step_key) {
            'provider_review' => 'awaiting_review',
            default => $flowRun->current_step_key,
        };
    }
}
