<?php

namespace App\Services\DrNetwork\Webhooks;

use App\Models\DrNetwork;
use App\Services\DrNetwork\Webhooks\Contracts\NetworkWebhookProcessor;
use RuntimeException;

class NetworkWebhookProcessorResolver
{
    public function resolve(DrNetwork $network): NetworkWebhookProcessor
    {
        $processorClass = config("dr_networks.webhook_processors.{$network->adapter_key}");

        if (! $processorClass || ! class_exists($processorClass)) {
            throw new RuntimeException("No webhook processor registered for adapter_key [{$network->adapter_key}].");
        }

        $processor = app($processorClass);

        if (! $processor instanceof NetworkWebhookProcessor) {
            throw new RuntimeException(sprintf(
                'Webhook processor [%s] must implement %s.',
                $processorClass,
                NetworkWebhookProcessor::class
            ));
        }

        return $processor;
    }
}
