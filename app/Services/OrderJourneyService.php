<?php

namespace App\Services;

use App\Models\DrNetworkFlowRun;
use App\Models\Order;

class OrderJourneyService
{
    public function build(Order $order): array
    {
        $order->loadMissing('flowRun');

        if ($order->payment_status === 'failed') {
            return $this->response(
                $order,
                'failed',
                'contact_support',
                null,
                'We could not complete your payment. Please contact support or try again.'
            );
        }

        if ($order->payment_status !== 'paid') {
            return $this->response(
                $order,
                'payment_pending',
                'wait',
                null,
                'We are waiting for your payment confirmation.',
                retryAfterSeconds: 3
            );
        }

        $flowRun = $order->flowRun;

        if (! $flowRun) {
            return $this->response(
                $order,
                'preparing',
                'wait',
                null,
                'We are preparing your next steps.',
                retryAfterSeconds: 2
            );
        }

        return match ($flowRun->status) {
            DrNetworkFlowRun::STATUS_PENDING => $this->response(
                $order,
                'preparing',
                'wait',
                null,
                'We are preparing your next steps.',
                retryAfterSeconds: 2
            ),
            DrNetworkFlowRun::STATUS_RUNNING, DrNetworkFlowRun::STATUS_PAUSED => $this->response(
                $order,
                'action_required',
                'open_workflow',
                'consultation',
                'Your consultation requirements are ready.',
                workflowUrl: "/api/v1/orders/{$order->id}/workflow/current-step",
                flowRun: $flowRun
            ),
            DrNetworkFlowRun::STATUS_COMPLETED => $this->response(
                $order,
                'completed',
                'complete',
                null,
                'Your consultation workflow is complete.',
                flowRun: $flowRun
            ),
            DrNetworkFlowRun::STATUS_FAILED => $this->response(
                $order,
                'failed',
                'contact_support',
                null,
                'We could not complete your consultation workflow. Please contact support.',
                flowRun: $flowRun
            ),
            DrNetworkFlowRun::STATUS_CANCELLED => $this->response(
                $order,
                'cancelled',
                'contact_support',
                null,
                'This order workflow has been cancelled.',
                flowRun: $flowRun
            ),
            default => $this->response(
                $order,
                'preparing',
                'wait',
                null,
                'We are preparing your next steps.',
                retryAfterSeconds: 2,
                flowRun: $flowRun
            ),
        };
    }

    private function response(
        Order $order,
        string $journeyStatus,
        string $nextAction,
        ?string $nextActionKey,
        string $message,
        ?int $retryAfterSeconds = null,
        ?string $workflowUrl = null,
        ?DrNetworkFlowRun $flowRun = null
    ): array {
        $response = [
            'order_status' => $this->orderStatus($order, $journeyStatus),
            'payment_status' => $order->payment_status,
            'journey_status' => $journeyStatus,
            'next_action' => $nextAction,
            'next_action_key' => $nextActionKey,
            'retry_after_seconds' => $retryAfterSeconds,
            'message' => $message,
        ];

        if ($workflowUrl !== null) {
            $response['workflow_url'] = $workflowUrl;
        }

        if ($flowRun !== null) {
            $response['workflow'] = [
                'status' => $flowRun->status,
                'current_step_key' => $flowRun->current_step_key,
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
}
