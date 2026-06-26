<?php

namespace App\Services\DrNetwork\Webhooks\Contracts;

use App\Models\DrNetworkWebhookEvent;

interface NetworkWebhookProcessor
{
    public function process(DrNetworkWebhookEvent $event): void;
}
