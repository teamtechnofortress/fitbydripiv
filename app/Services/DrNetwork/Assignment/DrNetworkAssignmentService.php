<?php

namespace App\Services\DrNetwork\Assignment;

use App\Exceptions\DrNetwork\NetworkAssignmentException;
use App\Models\DrNetworkFlowRun;
use App\Models\Order;
use App\Services\DrNetwork\Resolvers\NetworkStateResolver;
use App\Services\DrNetwork\Resolvers\ProductIdentifierResolver;
use Illuminate\Support\Facades\DB;

class DrNetworkAssignmentService
{
    public function __construct(
        private NetworkStateResolver $stateResolver,
        private ProductIdentifierResolver $identifierResolver,
    ) {
    }

    public function assign(Order $order): DrNetworkFlowRun
    {
        $existing = DrNetworkFlowRun::query()
            ->where('order_id', $order->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $stateCode = $this->stateCodeForOrder($order);

        if (! $stateCode) {
            throw new NetworkAssignmentException('No state is available for this order.');
        }

        $resolved = $this->stateResolver->resolve($stateCode);

        if (! $resolved) {
            throw new NetworkAssignmentException('No active network and flow are available for this state.');
        }

        $network = $resolved['network'];
        $flow = $resolved['flow'];
        $identifier = $this->identifierResolver->resolve($network->id, $order->product_id);

        if (! $identifier) {
            throw new NetworkAssignmentException('The purchased product is not mapped to the selected network.');
        }

        return DB::transaction(function () use ($order, $network, $flow, $identifier, $stateCode): DrNetworkFlowRun {
            $order->update([
                'state_code' => strtoupper($stateCode),
                'dr_network_id' => $network->id,
                'network_flow_id' => $flow->id,
                'network_flow_key' => $flow->flow_key,
                'network_product_identifier' => $identifier,
            ]);

            return DrNetworkFlowRun::query()->create([
                'order_id' => $order->id,
                'dr_network_id' => $network->id,
                'flow_id' => $flow->id,
                'status' => DrNetworkFlowRun::STATUS_PENDING,
                'context' => [
                    'state_code' => strtoupper($stateCode),
                    'network_product_identifier' => $identifier,
                ],
            ]);
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
