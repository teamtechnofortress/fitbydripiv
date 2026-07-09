<?php

namespace App\Services\DrNetwork\Assignment;

use App\Exceptions\DrNetwork\NetworkAssignmentException;
use App\Models\DrNetworkFlowRun;
use App\Models\NetworkFlowDefinition;
use App\Models\NetworkProductMapping;
use App\Models\Order;
use App\Services\DrNetwork\Resolvers\NetworkStateResolver;
use App\Services\DrNetwork\Resolvers\ProductIdentifierResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DrNetworkAssignmentService
{
    // private const OLA_HEALTH_FOLLOW_UP_ASYNC_FLOW_KEY = 'ola_health_follow_up_async_review';

    public function __construct(
        private NetworkStateResolver $stateResolver,
        private ProductIdentifierResolver $identifierResolver,
    ) {}

    public function assignRouting(Order $order): Order
    {
        Log::channel('dr_network')->info('Dr Network assignment started.', [
            'order_id' => $order->id,
            'order_state_code' => $order->state_code,
            'patient_id' => $order->patient_id,
            'product_id' => $order->product_id,
            'payment_status' => $order->payment_status,
        ]);

        $existing = DrNetworkFlowRun::query()
            ->with('flowDefinition')
            ->where('order_id', $order->id)
            ->first();

        if ($existing) {
            if ($existing->flowDefinition && $order->payment_status !== 'paid') {
                $order = $this->applyFlowFeesToOrder($order, $existing->flowDefinition);
            }

            Log::channel('dr_network')->info('Existing Dr Network flow run found; assignment skipped.', [
                'order_id' => $order->id,
                'flow_run_id' => $existing->id,
                'dr_network_id' => $existing->dr_network_id,
                'flow_id' => $existing->flow_id,
                'status' => $existing->status,
                'current_step_key' => $existing->current_step_key,
            ]);

            return $order->fresh();
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
        $flow = $this->flowForOrder($order, $network->id, $resolved['flow']);

        Log::channel('dr_network')->info('Resolved Dr Network and flow for state.', [
            'order_id' => $order->id,
            'state_code' => $stateCode,
            'dr_network_id' => $network->id,
            'adapter_key' => $network->adapter_key,
            'flow_id' => $flow->id,
            'flow_key' => $flow->flow_key,
            'billing_cycle_number' => $order->billing_cycle_number,
        ]);

        $productMapping = $this->identifierResolver->resolve($network->id, $order->product_id, $flow->id);

        if (! $productMapping) {
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
            'flow_id' => $flow->id,
            'external_service_id' => $productMapping->external_service_id,
            'external_service_key' => $productMapping->external_service_key,
        ]);

        return DB::transaction(function () use ($order, $network, $flow, $productMapping, $stateCode): Order {
            Log::channel('dr_network')->info('Persisting Dr Network assignment on order.', [
                'order_id' => $order->id,
                'state_code' => strtoupper($stateCode),
                'dr_network_id' => $network->id,
                'flow_id' => $flow->id,
                'flow_key' => $flow->flow_key,
                'network_fee_amount' => $flow->network_fee_amount,
                'patient_fee_amount' => $flow->patient_fee_amount,
            ]);

            $feeSnapshot = $this->feeSnapshotForFlow($order, $flow);

            $order->update([
                'state_code' => strtoupper($stateCode),
                'dr_network_id' => $network->id,
                'network_flow_id' => $flow->id,
                'network_flow_key' => $flow->flow_key,
                'network_product_identifier' => $productMapping->external_service_id,
                ...$feeSnapshot,
            ]);

            Log::channel('dr_network')->info('Dr Network routing assigned to order.', [
                'order_id' => $order->id,
                'dr_network_id' => $network->id,
                'flow_id' => $flow->id,
                'flow_key' => $flow->flow_key,
                'external_service_id' => $productMapping->external_service_id,
                'dr_network_fee_amount' => $feeSnapshot['dr_network_fee_amount'],
                'dr_network_patient_fee_amount' => $feeSnapshot['dr_network_patient_fee_amount'],
            ]);

            return $order->fresh();
        });
    }

    public function createFlowRunForAssignedOrder(Order $order): DrNetworkFlowRun
    {
        Log::channel('dr_network')->info('Dr Network flow run creation requested.', [
            'order_id' => $order->id,
            'payment_status' => $order->payment_status,
            'dr_network_id' => $order->dr_network_id,
            'network_flow_id' => $order->network_flow_id,
        ]);

        $existing = DrNetworkFlowRun::query()
            ->where('order_id', $order->id)
            ->first();

        if ($existing) {
            Log::channel('dr_network')->info('Existing Dr Network flow run found; creation skipped.', [
                'order_id' => $order->id,
                'flow_run_id' => $existing->id,
                'dr_network_id' => $existing->dr_network_id,
                'flow_id' => $existing->flow_id,
                'status' => $existing->status,
                'current_step_key' => $existing->current_step_key,
            ]);

            return $existing;
        }

        if (! $order->dr_network_id || ! $order->network_flow_id || ! $order->network_flow_key) {
            Log::channel('dr_network')->warning('Dr Network flow start failed: order has no assigned routing.', [
                'order_id' => $order->id,
                'dr_network_id' => $order->dr_network_id,
                'network_flow_id' => $order->network_flow_id,
                'network_flow_key' => $order->network_flow_key,
            ]);

            throw new NetworkAssignmentException('Doctor network routing has not been assigned for this order.');
        }

        $productMapping = $this->identifierResolver->resolve(
            (int) $order->dr_network_id,
            $order->product_id,
            (int) $order->network_flow_id
        );

        if (! $productMapping) {
            Log::channel('dr_network')->warning('Dr Network flow start failed: assigned product mapping is inactive or missing.', [
                'order_id' => $order->id,
                'product_id' => $order->product_id,
                'dr_network_id' => $order->dr_network_id,
                'flow_id' => $order->network_flow_id,
            ]);

            throw new NetworkAssignmentException('The assigned product mapping is no longer available.');
        }

        return DB::transaction(function () use ($order, $productMapping): DrNetworkFlowRun {
            $networkProductContext = $this->networkProductContext($productMapping);
            $stateCode = $this->stateCodeForOrder($order);

            $flowRun = DrNetworkFlowRun::query()->create([
                'order_id' => $order->id,
                'dr_network_id' => $order->dr_network_id,
                'flow_id' => $order->network_flow_id,
                'status' => DrNetworkFlowRun::STATUS_PENDING,
                'context' => [
                    'state_code' => $stateCode ? strtoupper($stateCode) : null,
                    ...$networkProductContext,
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

    private function networkProductContext(NetworkProductMapping $mapping): array
    {
        $externalConfig = $mapping->external_config ?? [];
        $sessionType = $externalConfig['session_type'] ?? null;

        return array_filter([
            'network_product_mapping_id' => $mapping->id,
            'network_product_identifier' => $mapping->external_service_id,
            'external_service_id' => $mapping->external_service_id,
            'external_service_key' => $mapping->external_service_key,
            'external_config' => $externalConfig,
            'service_id' => $mapping->external_service_id,
            'service_key' => $mapping->external_service_key,
            'session_type' => $sessionType,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    private function applyFlowFeesToOrder(Order $order, NetworkFlowDefinition $flow): Order
    {
        if ($order->payment_status === 'paid') {
            return $order->fresh();
        }

        $order->update($this->feeSnapshotForFlow($order, $flow));

        return $order->fresh();
    }

    private function feeSnapshotForFlow(Order $order, NetworkFlowDefinition $flow): array
    {
        $previousPatientFee = round((float) ($order->dr_network_patient_fee_amount ?? 0), 2);
        $patientFee = round((float) ($flow->patient_fee_amount ?? 0), 2);
        $networkFee = round((float) ($flow->network_fee_amount ?? 0), 2);

        $baseAmount = $this->replacePatientFee((float) ($order->base_amount ?? 0), $previousPatientFee, $patientFee);
        $finalAmount = $this->replacePatientFee((float) ($order->final_amount ?? $order->price ?? 0), $previousPatientFee, $patientFee);

        return [
            'dr_network_fee_amount' => $networkFee,
            'dr_network_patient_fee_amount' => $patientFee,
            'base_amount' => $baseAmount,
            'final_amount' => $finalAmount,
            'price' => $finalAmount,
        ];
    }

    private function replacePatientFee(float $amount, float $previousPatientFee, float $patientFee): float
    {
        return max(0, round($amount - $previousPatientFee + $patientFee, 2));
    }

    private function flowForOrder(Order $order, int $networkId, NetworkFlowDefinition $resolvedFlow): NetworkFlowDefinition
    {
        return $resolvedFlow;

        // Follow-up flow selection is temporarily disabled.
        // if (! $this->isFollowUpOrder($order)) {
        //     return $resolvedFlow;
        // }
        //
        // $followUpFlow = NetworkFlowDefinition::query()
        //     ->active()
        //     ->forNetwork($networkId)
        //     ->forKey(self::OLA_HEALTH_FOLLOW_UP_ASYNC_FLOW_KEY)
        //     ->first();
        //
        // if (! $followUpFlow) {
        //     return $resolvedFlow;
        // }
        //
        // $hasFollowUpMapping = NetworkProductMapping::query()
        //     ->forNetwork($networkId)
        //     ->forProduct($order->product_id)
        //     ->forFlow($followUpFlow->id)
        //     ->active()
        //     ->exists();
        //
        // return $hasFollowUpMapping ? $followUpFlow : $resolvedFlow;
    }

    // private function isFollowUpOrder(Order $order): bool
    // {
    //     return $order->purchase_type === Order::PRICING_TYPE_SUBSCRIPTION
    //         && (int) ($order->billing_cycle_number ?? 1) > 1;
    // }

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
