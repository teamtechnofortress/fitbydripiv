<?php

namespace App\Services\DrNetwork\ProviderScheduling;

use App\Exceptions\DrNetwork\SlotUnavailableException;
use App\Models\Order;
use App\Services\DrNetwork\Core\DrNetworkOrchestrator;
use App\Services\DrNetwork\Flow\FlowRunner;
use Illuminate\Support\Carbon;
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
        $context = $order->flowRun?->context ?? [];

        return $adapter->getAvailableSlots(array_merge([
            'state_code' => $order->state_code,
            'network_product_identifier' => $context['network_product_identifier']
                ?? $context['external_service_id']
                ?? $order->network_product_identifier,
            'service_id' => $context['service_id'] ?? $context['external_service_id'] ?? null,
            'service_key' => $context['service_key'] ?? $context['external_service_key'] ?? null,
            'session_type' => $context['session_type'] ?? null,
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
            $confirmation = $adapter->bookSlot($slotId, array_merge($slotMeta, [
                'order_reference' => (string) $order->id,
                'patient_name' => trim(($order->patient?->first_name ?? '').' '.($order->patient?->last_name ?? '')),
            ]));
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
                'slot_scheduled_at' => $confirmation['schedule_start_date'] ?? $confirmation['scheduled_time'] ?? null,
                'slot_schedule_end_at' => $confirmation['schedule_end_date']
                    ?? $this->fallbackEndDate($confirmation['schedule_start_date'] ?? $confirmation['scheduled_time'] ?? null, $slotMeta['appt_length'] ?? null),
                'slot_schedule_id' => $confirmation['schedule_id'] ?? null,
                'provider_id' => $confirmation['provider_guid'] ?? $confirmation['provider_id'] ?? null,
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

    private function fallbackEndDate(?string $startDate, mixed $appointmentLength): ?string
    {
        if (! $startDate) {
            return null;
        }

        $minutes = is_numeric($appointmentLength) ? max(1, (int) $appointmentLength) : 15;

        return Carbon::parse($startDate)->addMinutes($minutes)->toJSON();
    }
}
