<?php

namespace App\Services\DrNetwork\Webhooks;

use App\Models\DrNetwork;
use App\Models\DrNetworkWebhookEvent;
use App\Services\DrNetwork\Webhooks\DTO\NormalizedNetworkWebhook;
use Illuminate\Http\Request;

class NetworkWebhookEventService
{
    public function store(
        DrNetwork $network,
        NormalizedNetworkWebhook $webhook,
        Request $request
    ): DrNetworkWebhookEvent {
        $rawBody = $request->getContent();

        return DrNetworkWebhookEvent::query()->firstOrCreate(
            [
                'dr_network_id' => $network->id,
                'idempotency_hash' => $webhook->idempotencyHash($rawBody),
            ],
            [
                'adapter_key' => $network->adapter_key,
                'event_type' => $webhook->eventType,
                'external_event_id' => $webhook->externalEventId,
                'external_case_id' => $webhook->externalCaseId,
                'external_order_id' => $webhook->externalOrderId,
                'status' => DrNetworkWebhookEvent::STATUS_PENDING,
                'headers' => $this->headers($request),
                'payload' => $request->all(),
                'normalized_payload' => $webhook->toArray(),
                'raw_body' => $rawBody,
                'occurred_at' => $webhook->occurredAtCarbon(),
            ]
        );
    }

    private function headers(Request $request): array
    {
        return collect($request->headers->all())
            ->map(fn (array $values): array => array_values(array_map('strval', $values)))
            ->all();
    }
}
