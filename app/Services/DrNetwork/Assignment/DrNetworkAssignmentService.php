<?php

namespace App\Services\DrNetwork\Assignment;

use App\Exceptions\DrNetwork\NetworkAssignmentException;
use App\Models\DrNetworkFlowRun;
use App\Models\Order;
use App\Services\DrNetwork\Resolvers\NetworkStateResolver;
use App\Services\DrNetwork\Resolvers\ProductIdentifierResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DrNetworkAssignmentService
{
    public function __construct(
        private NetworkStateResolver $stateResolver,
        private ProductIdentifierResolver $identifierResolver,
    ) {}

    public function assign(Order $order): DrNetworkFlowRun
    {
        Log::channel('dr_network')->info('Dr Network assignment started.', [
            'order_id' => $order->id,
            'order_state_code' => $order->state_code,
            'patient_id' => $order->patient_id,
            'product_id' => $order->product_id,
        ]);

        $existing = DrNetworkFlowRun::query()
            ->where('order_id', $order->id)
            ->first();

        if ($existing) {
            Log::channel('dr_network')->info('Existing Dr Network flow run found; assignment skipped.', [
                'order_id' => $order->id,
                'flow_run_id' => $existing->id,
                'dr_network_id' => $existing->dr_network_id,
                'flow_id' => $existing->flow_id,
                'status' => $existing->status,
                'current_step_key' => $existing->current_step_key,
            ]);

            return $existing;
        }

        $stateCode = $this->stateCodeForOrder($order);

        if (! $stateCode) {
            Log::channel('dr_network')->warning('Dr Network assignment failed: no state is available.', [
                'order_id' => $order->id,
                'order_state_code' => $order->state_code,
                'patient_id' => $order->patient_id,
            ]);

            throw new NetworkAssignmentException('No state is available for this order.');
        }

        Log::channel('dr_network')->info('Resolved order state for Dr Network assignment.', [
            'order_id' => $order->id,
            'state_code' => $stateCode,
        ]);

        $resolved = $this->stateResolver->resolve($stateCode);

        if (! $resolved) {
            Log::channel('dr_network')->warning('Dr Network assignment failed: no active network and flow for state.', [
                'order_id' => $order->id,
                'state_code' => $stateCode,
            ]);

            throw new NetworkAssignmentException('No active network and flow are available for this state.');
        }

        $network = $resolved['network'];
        $flow = $resolved['flow'];

        Log::channel('dr_network')->info('Resolved Dr Network and flow for state.', [
            'order_id' => $order->id,
            'state_code' => $stateCode,
            'dr_network_id' => $network->id,
            'adapter_key' => $network->adapter_key,
            'flow_id' => $flow->id,
            'flow_key' => $flow->flow_key,
        ]);

        $identifier = $this->identifierResolver->resolve($network->id, $order->product_id);

        if (! $identifier) {
            Log::channel('dr_network')->warning('Dr Network assignment failed: product is not mapped to selected network.', [
                'order_id' => $order->id,
                'product_id' => $order->product_id,
                'dr_network_id' => $network->id,
                'flow_id' => $flow->id,
            ]);

            throw new NetworkAssignmentException('The purchased product is not mapped to the selected network.');
        }

        Log::channel('dr_network')->info('Resolved network product identifier for assignment.', [
            'order_id' => $order->id,
            'product_id' => $order->product_id,
            'dr_network_id' => $network->id,
            'network_product_identifier' => $identifier,
        ]);

        return DB::transaction(function () use ($order, $network, $flow, $identifier, $stateCode): DrNetworkFlowRun {
            Log::channel('dr_network')->info('Persisting Dr Network assignment on order.', [
                'order_id' => $order->id,
                'state_code' => strtoupper($stateCode),
                'dr_network_id' => $network->id,
                'flow_id' => $flow->id,
                'flow_key' => $flow->flow_key,
            ]);

            $order->update([
                'state_code' => strtoupper($stateCode),
                'dr_network_id' => $network->id,
                'network_flow_id' => $flow->id,
                'network_flow_key' => $flow->flow_key,
                'network_product_identifier' => $identifier,
            ]);

            $flowRun = DrNetworkFlowRun::query()->create([
                'order_id' => $order->id,
                'dr_network_id' => $network->id,
                'flow_id' => $flow->id,
                'status' => DrNetworkFlowRun::STATUS_PENDING,
                'context' => [
                    'state_code' => strtoupper($stateCode),
                    'network_product_identifier' => $identifier,
                ],
            ]);

            Log::channel('dr_network')->info('Dr Network flow run created.', [
                'order_id' => $flowRun->order_id,
                'flow_run_id' => $flowRun->id,
                'dr_network_id' => $flowRun->dr_network_id,
                'flow_id' => $flowRun->flow_id,
                'status' => $flowRun->status,
            ]);

            return $flowRun;
        });
    }

    private function stateCodeForOrder(Order $order): ?string
    {
        if ($order->state_code) {
            return strtoupper(trim($order->state_code));
        }

        $order->loadMissing('patient');

        if (! $order->patient?->state) {
            return null;
        }

        return strtoupper(trim($order->patient->state));
    }
}
