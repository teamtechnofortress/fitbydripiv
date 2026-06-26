<?php

namespace App\Services\DrNetwork\ConsultationManagement;

use App\Models\ConsultationRecord;
use App\Models\DrNetworkFlowRun;
use App\Models\Order;
use App\Services\DrNetwork\Adapters\OlaHealth\OlaHealthStatusMapper;
use App\Services\DrNetwork\Flow\FlowRunner;
use App\Services\DrNetwork\Resolvers\NetworkAdapterResolver;

class ConsultationStatusService
{
    public function __construct(
        private FlowRunner $flowRunner,
        private NetworkAdapterResolver $adapterResolver,
    ) {}

    public function handleNetworkStatusUpdate(Order $order, string $networkStatus, array $rawPayload = []): void
    {
        $order->loadMissing(['drNetwork', 'flowRun']);

        if (! $order->drNetwork || ! $order->flowRun) {
            return;
        }

        $adapter = $this->adapterResolver->resolve($order->drNetwork);
        $internalStatus = $adapter->translateStatus($networkStatus);

        ConsultationRecord::query()
            ->where('order_id', $order->id)
            ->latest('id')
            ->first()
            ?->update([
                'network_status' => $networkStatus,
                'internal_status' => $internalStatus,
                'network_metadata' => $rawPayload,
                'resolved_at' => in_array($internalStatus, [
                    OlaHealthStatusMapper::INTERNAL_PRESCRIPTION_APPROVED,
                    OlaHealthStatusMapper::INTERNAL_CONSULTATION_REJECTED,
                ], true) ? now() : null,
            ]);

        $flowRun = $order->flowRun;

        if (! in_array($flowRun->status, [DrNetworkFlowRun::STATUS_RUNNING, DrNetworkFlowRun::STATUS_PAUSED], true)) {
            return;
        }

        match ($internalStatus) {
            OlaHealthStatusMapper::INTERNAL_PRESCRIPTION_APPROVED => $this->flowRunner->complete($flowRun, [
                'outcome' => 'approved',
                'raw' => $rawPayload,
            ]),
            OlaHealthStatusMapper::INTERNAL_CONSULTATION_REJECTED => $this->flowRunner->fail($flowRun, 'rejected_by_provider', [
                'raw' => $rawPayload,
            ]),
            OlaHealthStatusMapper::INTERNAL_PENDING_PATIENT_INFO => $this->flowRunner->pause($flowRun, 'pending_patient_info'),
            default => null,
        };
    }
}
