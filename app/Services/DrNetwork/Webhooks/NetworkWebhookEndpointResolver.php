<?php

namespace App\Services\DrNetwork\Webhooks;

use App\Models\DrNetwork;
use App\Models\DrNetworkConfigValue;
use RuntimeException;

class NetworkWebhookEndpointResolver
{
    public function resolve(string $endpointToken): DrNetwork
    {
        $configValue = DrNetworkConfigValue::query()
            ->where('key', DrNetworkConfigValue::normalizeKey('webhook_endpoint_token'))
            ->where('lookup_hash', DrNetworkConfigValue::lookupHash($endpointToken))
            ->with('drNetwork.configValues')
            ->first();

        if (! $configValue?->drNetwork) {
            throw new RuntimeException('Unknown doctor network webhook endpoint.');
        }

        if (! $configValue->drNetwork->configValue('webhook_enabled', true)) {
            throw new RuntimeException('Doctor network webhook endpoint is disabled.');
        }

        return $configValue->drNetwork;
    }
}
