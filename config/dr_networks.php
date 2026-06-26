<?php

use App\Services\DrNetwork\Adapters\OlaHealth\OlaHealthAdapter;
use App\Services\DrNetwork\Adapters\OlaHealth\Webhooks\OlaHealthWebhookAdapter;
use App\Services\DrNetwork\Adapters\OlaHealth\Webhooks\OlaHealthWebhookHandler;

return [
    'adapters' => [
        'ola_health' => OlaHealthAdapter::class,
    ],

    'webhook_adapters' => [
        'ola_health' => OlaHealthWebhookAdapter::class,
    ],

    'webhook_processors' => [
        'ola_health' => OlaHealthWebhookHandler::class,
    ],
];
