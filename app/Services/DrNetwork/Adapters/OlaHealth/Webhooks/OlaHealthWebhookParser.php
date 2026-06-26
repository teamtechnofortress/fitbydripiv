<?php

namespace App\Services\DrNetwork\Adapters\OlaHealth\Webhooks;

use App\Models\DrNetwork;
use App\Services\DrNetwork\Webhooks\DTO\NormalizedNetworkWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

class OlaHealthWebhookParser
{
    public function normalize(Request $request, DrNetwork $network): NormalizedNetworkWebhook
    {
        $payload = $request->all();

        return new NormalizedNetworkWebhook(
            eventType: (string) ($payload['event_type'] ?? $payload['event'] ?? $payload['type'] ?? 'unknown'),
            externalCaseId: $this->nullableString($payload['case_id'] ?? $payload['schedule_id'] ?? null),
            externalOrderId: $this->nullableString($payload['order_guid'] ?? $payload['order_id'] ?? $payload['order_reference'] ?? null),
            payload: $payload,
            externalEventId: $this->nullableString(
                $payload['event_id']
                    ?? $payload['webhook_id']
                    ?? $request->header('X-Ola-Delivery-Id')
                    ?? $request->header('X-Delivery-Id')
                    ?? null
            ),
            occurredAt: $this->occurredAt($payload)
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private function occurredAt(array $payload): ?Carbon
    {
        $value = $payload['occurred_at'] ?? $payload['created_at'] ?? $payload['timestamp'] ?? null;

        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
