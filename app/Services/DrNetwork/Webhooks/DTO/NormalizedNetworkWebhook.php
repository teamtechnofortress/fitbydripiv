<?php

namespace App\Services\DrNetwork\Webhooks\DTO;

use DateTimeInterface;
use Illuminate\Support\Carbon;

class NormalizedNetworkWebhook
{
    public function __construct(
        public readonly string $eventType,
        public readonly ?string $externalCaseId,
        public readonly ?string $externalOrderId,
        public readonly array $payload,
        public readonly ?string $externalEventId = null,
        public readonly ?DateTimeInterface $occurredAt = null,
    ) {}

    public function occurredAtCarbon(): Carbon
    {
        return $this->occurredAt
            ? Carbon::instance($this->occurredAt)
            : now();
    }

    public function idempotencyHash(string $rawBody): string
    {
        return hash('sha256', implode('|', [
            $this->externalEventId ?: '',
            $this->eventType,
            $this->externalCaseId ?: '',
            $this->externalOrderId ?: '',
            $rawBody,
        ]));
    }

    public function toArray(): array
    {
        return [
            'event_type' => $this->eventType,
            'external_event_id' => $this->externalEventId,
            'external_case_id' => $this->externalCaseId,
            'external_order_id' => $this->externalOrderId,
            'occurred_at' => $this->occurredAtCarbon()->toISOString(),
            'payload' => $this->payload,
        ];
    }
}
