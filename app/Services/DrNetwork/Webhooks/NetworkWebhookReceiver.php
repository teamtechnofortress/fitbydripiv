<?php

namespace App\Services\DrNetwork\Webhooks;

use App\Jobs\DrNetwork\ProcessNetworkWebhook;
use App\Models\DrNetwork;
use App\Models\DrNetworkWebhookEvent;
use Illuminate\Http\Request;

class NetworkWebhookReceiver
{
    public function __construct(
        private NetworkWebhookAdapterResolver $adapterResolver,
        private NetworkWebhookEventService $eventService,
    ) {}

    public function receive(Request $request, DrNetwork $network): DrNetworkWebhookEvent
    {
        $adapter = $this->adapterResolver->resolve($network);
        $webhook = $adapter->normalize($request, $network);
        $event = $this->eventService->store($network, $webhook, $request);

        if ($event->wasRecentlyCreated || $event->status === DrNetworkWebhookEvent::STATUS_FAILED) {
            ProcessNetworkWebhook::dispatch($event->id);
        }

        return $event;
    }
}
