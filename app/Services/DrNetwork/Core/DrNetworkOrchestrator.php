<?php

namespace App\Services\DrNetwork\Core;

use App\Models\DrNetworkFlowRun;
use App\Models\Order;
use App\Services\DrNetwork\Adapters\Contracts\DoctorNetworkAdapter;
use App\Services\DrNetwork\Assignment\DrNetworkAssignmentService;
use App\Services\DrNetwork\Flow\FlowRunner;
use App\Services\DrNetwork\Resolvers\NetworkAdapterResolver;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DrNetworkOrchestrator
{
    public function __construct(
        private DrNetworkAssignmentService $assignmentService,
        private FlowRunner $flowRunner,
        private NetworkAdapterResolver $adapterResolver,
    ) {}

    public function startForOrder(Order $order): DrNetworkFlowRun
    {
        Log::channel('dr_network')->info('Starting Dr Network flow for order.', [
            'order_id' => $order->id,
            'payment_status' => $order->payment_status,
            'state_code' => $order->state_code,
            'product_id' => $order->product_id,
        ]);

        $flowRun = $this->assignmentService->createFlowRunForAssignedOrder($order);

        if ($flowRun->status === DrNetworkFlowRun::STATUS_PENDING) {
            Log::channel('dr_network')->info('Flow run pending; starting first step.', [
                'order_id' => $order->id,
                'flow_run_id' => $flowRun->id,
                'dr_network_id' => $flowRun->dr_network_id,
                'flow_id' => $flowRun->flow_id,
            ]);

            $this->flowRunner->start($flowRun);
        } else {
            Log::channel('dr_network')->info('Flow run already started; skipping start.', [
                'order_id' => $order->id,
                'flow_run_id' => $flowRun->id,
                'status' => $flowRun->status,
                'current_step_key' => $flowRun->current_step_key,
            ]);
        }

        $flowRun = $flowRun->refresh();

        Log::channel('dr_network')->info('Dr Network flow start finished.', [
            'order_id' => $order->id,
            'flow_run_id' => $flowRun->id,
            'status' => $flowRun->status,
            'current_step_key' => $flowRun->current_step_key,
        ]);

        return $flowRun;
    }

    public function adapterFor(Order $order): DoctorNetworkAdapter
    {
        $order->loadMissing('drNetwork');

        if (! $order->drNetwork) {
            Log::channel('dr_network')->warning('Adapter resolution failed: order has no assigned Dr Network.', [
                'order_id' => $order->id,
            ]);

            throw new RuntimeException('Order has not been assigned to a doctor network.');
        }

        Log::channel('dr_network')->info('Resolving Dr Network adapter for order.', [
            'order_id' => $order->id,
            'dr_network_id' => $order->drNetwork->id,
            'adapter_key' => $order->drNetwork->adapter_key,
        ]);

        return $this->adapterResolver->resolve($order->drNetwork);
    }
}
