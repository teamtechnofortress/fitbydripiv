<?php

namespace App\Services\DrNetwork\Flow;

use App\Models\DrNetworkFlowRun;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DrNetworkFlowFailureService
{
    private const TERMINAL_STATUSES = [
        DrNetworkFlowRun::STATUS_FAILED,
        DrNetworkFlowRun::STATUS_COMPLETED,
        DrNetworkFlowRun::STATUS_CANCELLED,
    ];

    public function __construct(
        private FlowRunner $flowRunner,
    ) {}

    public function failOrder(Order $order, string $reason, array $context = []): DrNetworkFlowRun
    {
        $order->loadMissing('flowRun');

        if (! $order->flowRun) {
            Log::channel('dr_network')->warning('Dr Network hard stop requested but order has no flow run.', [
                'order_id' => $order->id,
                'reason' => $reason,
                'context' => $context,
            ]);

            throw new RuntimeException('Cannot fail Dr Network flow: flow run does not exist.');
        }

        return $this->failRun($order->flowRun, $reason, $context);
    }

    public function failRun(DrNetworkFlowRun $flowRun, string $reason, array $context = []): DrNetworkFlowRun
    {
        if (in_array($flowRun->status, self::TERMINAL_STATUSES, true)) {
            Log::channel('dr_network')->info('Dr Network hard stop skipped because flow run is already terminal.', [
                'flow_run_id' => $flowRun->id,
                'order_id' => $flowRun->order_id,
                'status' => $flowRun->status,
                'current_step_key' => $flowRun->current_step_key,
                'reason' => $reason,
            ]);

            return $flowRun->refresh();
        }

        Log::channel('dr_network')->warning('Dr Network flow hard stop requested.', [
            'flow_run_id' => $flowRun->id,
            'order_id' => $flowRun->order_id,
            'status' => $flowRun->status,
            'current_step_key' => $flowRun->current_step_key,
            'reason' => $reason,
            'context' => $context,
        ]);

        return $this->flowRunner->fail($flowRun, $reason, array_merge($context, [
            'failed_step_key' => $flowRun->current_step_key,
        ]));
    }
}
