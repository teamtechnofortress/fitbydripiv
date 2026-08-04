<?php

namespace App\Services\DrNetwork\Adapters\OlaHealth\Webhooks;

use App\Models\ConsultationRecord;
use App\Models\DrNetworkWebhookEvent;
use App\Services\DrNetwork\ConsultationManagement\ConsultationStatusService;
use App\Services\DrNetwork\Resolvers\NetworkAdapterResolver;
use App\Services\DrNetwork\Webhooks\Contracts\NetworkWebhookProcessor;
use Illuminate\Support\Facades\Log;

class OlaHealthWebhookHandler implements NetworkWebhookProcessor
{
    public function __construct(
        private ConsultationStatusService $statusService,
        private NetworkAdapterResolver $adapterResolver,
    ) {}

    public function process(DrNetworkWebhookEvent $event): void
    {
        $event->loadMissing('drNetwork');

        $externalCaseId = $event->external_case_id;

        if (! $externalCaseId) {
            Log::channel('dr_network')->warning('Ola webhook missing external case id.', [
                'webhook_event_id' => $event->id,
                'event_type' => $event->event_type,
            ]);

            return;
        }

        $consultationRecord = ConsultationRecord::query()
            ->where('network_case_id', $externalCaseId)
            ->with('order')
            ->first();

        if (! $consultationRecord?->order) {
            Log::channel('dr_network')->warning("No local consultation record for Ola case [{$externalCaseId}].", [
                'webhook_event_id' => $event->id,
            ]);

            return;
        }

        [$networkStatus, $payload] = $this->resolveAuthoritativeStatus($event, $externalCaseId);

        if (! $networkStatus) {
            Log::channel('dr_network')->info('Ola webhook did not include or resolve a status.', [
                'webhook_event_id' => $event->id,
                'event_type' => $event->event_type,
                'external_case_id' => $externalCaseId,
            ]);

            return;
        }

        $this->statusService->handleNetworkStatusUpdate(
            $consultationRecord->order,
            $networkStatus,
            $payload
        );
    }

    private function resolveAuthoritativeStatus(DrNetworkWebhookEvent $event, string $externalCaseId): array
    {
        if ($event->event_type === 'case_completed') {
            $adapter = $this->adapterResolver->resolve($event->drNetwork);
            $statusPayload = $adapter->getCaseStatus($externalCaseId);

            return [
                'completed',
                [
                    'webhook_payload' => $event->payload ?? [],
                    'status_lookup' => $statusPayload,
                ],
            ];
        }

        $payload = $event->payload ?? [];
        $networkStatus = $payload['status'] ?? $payload['network_status'] ?? null;

        if (! $networkStatus && $event->event_type === 'prescription_submitted') {
            $networkStatus = $this->statusFromPrescriptionPayload($payload);
        }

        return [
            $networkStatus ? (string) $networkStatus : null,
            $payload,
        ];
    }

    private function statusFromPrescriptionPayload(array $payload): ?string
    {
        $prescriptionStatus = strtolower((string) (
            $payload['prescription_status']
            ?? $payload['prescription']['status']
            ?? $payload['prescription'][0]['status']
            ?? ''
        ));

        return match ($prescriptionStatus) {
            'accept', 'accepted', 'approved' => 'prescription_issued',
            'reject', 'rejected', 'declined' => 'rejected',
            default => null,
        };
    }
}
