<?php

namespace App\Services\DrNetwork\Webhooks;

use App\Models\DrNetwork;
use App\Services\DrNetwork\Webhooks\Contracts\NetworkWebhookAdapter;
use RuntimeException;

class NetworkWebhookAdapterResolver
{
    public function resolve(DrNetwork $network): NetworkWebhookAdapter
    {
        $adapterClass = config("dr_networks.webhook_adapters.{$network->adapter_key}");

        if (! $adapterClass || ! class_exists($adapterClass)) {
            throw new RuntimeException("No webhook adapter registered for adapter_key [{$network->adapter_key}].");
        }

        $adapter = app($adapterClass);

        if (! $adapter instanceof NetworkWebhookAdapter) {
            throw new RuntimeException(sprintf(
                'Webhook adapter [%s] must implement %s.',
                $adapterClass,
                NetworkWebhookAdapter::class
            ));
        }

        return $adapter;
    }
}
