<?php

namespace App\Services\DrNetwork\ProviderScheduling;

use App\Exceptions\DrNetwork\SlotUnavailableException;
use App\Models\Order;
use App\Services\DrNetwork\Core\DrNetworkOrchestrator;
use App\Services\DrNetwork\Flow\FlowRunner;
use RuntimeException;

class ProviderSlotService
{
    public function __construct(
        private DrNetworkOrchestrator $orchestrator,
        private FlowRunner $flowRunner,
    ) {}

    public function getAvailableSlots(Order $order, array $filters = []): array
    {
        $order->loadMissing('flowRun');
        $adapter = $this->orchestrator->adapterFor($order);

        return $adapter->getAvailableSlots(array_merge([
            'service_id' => $order->flowRun?->context['network_product_identifier']
                ?? $order->network_product_identifier,
        ], $filters));
    }

    public function bookSlot(Order $order, string $slotId, array $slotMeta = []): array
    {
        $order->loadMissing(['flowRun', 'patient']);
        $adapter = $this->orchestrator->adapterFor($order);
        $flowRun = $order->flowRun;

        if (! $flowRun) {
            throw new RuntimeException('Order does not have a doctor network flow run.');
        }

        try {
            $confirmation = $adapter->bookSlot($slotId, [
                'order_reference' => (string) $order->id,
                'patient_name' => trim(($order->patient?->first_name ?? '').' '.($order->patient?->last_name ?? '')),
            ]);
        } catch (RuntimeException $e) {
            if ($this->isSlotConflict($e)) {
                throw new SlotUnavailableException(
                    'This time slot is no longer available. Please select another.',
                    previous: $e
                );
            }

            throw $e;
        }

        $flowRun->update([
            'context' => array_merge($flowRun->context ?? [], [
                'slot_id' => $slotId,
                'slot_meta' => $slotMeta,
                'slot_scheduled_at' => $confirmation['scheduled_time'] ?? null,
                'slot_schedule_id' => $confirmation['schedule_id'] ?? null,
                'provider_id' => $confirmation['provider_id'] ?? null,
            ]),
        ]);

        if ($flowRun->current_step_key === 'slot_selection') {
            $this->flowRunner->advance($flowRun->refresh(), 'slot_selection', $confirmation);
        }

        return $confirmation;
    }

    private function isSlotConflict(RuntimeException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'not available')
            || str_contains($message, 'already booked')
            || str_contains($message, 'conflict');
    }
}
