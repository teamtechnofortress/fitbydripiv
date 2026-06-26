<?php

namespace App\Services\DrNetwork\Webhooks;

use App\Models\DrNetworkWebhookEvent;

class NetworkWebhookHandler
{
    public function __construct(
        private NetworkWebhookProcessorResolver $processorResolver,
    ) {}

    public function handle(DrNetworkWebhookEvent $event): void
    {
        $event->loadMissing('drNetwork');

        $this->processorResolver
            ->resolve($event->drNetwork)
            ->process($event);
    }
}
