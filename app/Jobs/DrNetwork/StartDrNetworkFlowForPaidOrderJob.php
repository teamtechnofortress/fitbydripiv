<?php

namespace App\Jobs\DrNetwork;

use App\Exceptions\DrNetwork\NetworkAssignmentException;
use App\Models\Order;
use App\Services\DrNetwork\Core\DrNetworkOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class StartDrNetworkFlowForPaidOrderJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $uniqueFor = 3600;

    public function __construct(
        public int $orderId,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->orderId;
    }

    public function handle(DrNetworkOrchestrator $orchestrator): void
    {
        Log::channel('dr_network')->info('Dr Network start job picked up.', [
            'order_id' => $this->orderId,
        ]);

        $order = Order::query()
            ->with('patient')
            ->find($this->orderId);

        if (! $order) {
            Log::channel('dr_network')->warning('Skipping Dr Network start: order no longer exists.', [
                'order_id' => $this->orderId,
            ]);

            return;
        }

        Log::channel('dr_network')->info('Dr Network start job loaded order.', [
            'order_id' => $order->id,
            'order_uuid' => $order->order_uuid,
            'payment_status' => $order->payment_status,
            'dr_network_id' => $order->dr_network_id,
            'network_flow_id' => $order->network_flow_id,
            'network_flow_key' => $order->network_flow_key,
            'patient_id' => $order->patient_id,
            'product_id' => $order->product_id,
        ]);

        try {
            $flowRun = $orchestrator->startForOrder($order);
        } catch (NetworkAssignmentException $e) {
            Log::channel('dr_network')->warning('Unable to start Dr Network flow for paid order.', [
                'order_id' => $order->id,
                'reason' => $e->getMessage(),
                'state_code' => $order->state_code,
                'patient_state' => $order->patient?->state,
                'patient_id' => $order->patient_id,
                'product_id' => $order->product_id,
            ]);

            return;
        } catch (Throwable $e) {
            Log::channel('dr_network')->error('Unexpected Dr Network start failure for paid order.', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
                'state_code' => $order->state_code,
                'patient_state' => $order->patient?->state,
                'patient_id' => $order->patient_id,
                'product_id' => $order->product_id,
            ]);

            return;
        }

        Log::channel('dr_network')->info('Dr Network flow started for paid order.', [
            'order_id' => $order->id,
            'flow_run_id' => $flowRun->id,
            'status' => $flowRun->status,
            'current_step_key' => $flowRun->current_step_key,
        ]);
    }
}
