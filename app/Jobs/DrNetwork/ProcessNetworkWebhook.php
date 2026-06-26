<?php

namespace App\Jobs\DrNetwork;

use App\Models\DrNetworkWebhookEvent;
use App\Services\DrNetwork\Webhooks\NetworkWebhookHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessNetworkWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        private int $eventId,
    ) {}

    public function handle(NetworkWebhookHandler $handler): void
    {
        $event = DrNetworkWebhookEvent::query()->findOrFail($this->eventId);

        if ($event->status === DrNetworkWebhookEvent::STATUS_PROCESSED) {
            return;
        }

        $event->update([
            'status' => DrNetworkWebhookEvent::STATUS_PROCESSING,
            'failure_reason' => null,
        ]);

        try {
            $handler->handle($event);

            $event->update([
                'status' => DrNetworkWebhookEvent::STATUS_PROCESSED,
                'processed_at' => now(),
            ]);
        } catch (Throwable $e) {
            $event->update([
                'status' => DrNetworkWebhookEvent::STATUS_FAILED,
                'failure_reason' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
