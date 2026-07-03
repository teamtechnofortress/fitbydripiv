<?php

namespace App\Services\DrNetwork\Webhooks;

use App\Models\DrNetwork;
use App\Models\DrNetworkConfigValue;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class NetworkWebhookEndpointResolver
{
    public function resolve(string $endpointToken): DrNetwork
    {
        $endpointTokenHash = DrNetworkConfigValue::lookupHash($endpointToken);
        $network = DrNetwork::query()
            ->where(fn (Builder $query): Builder => $query
                ->where('settings->webhook_endpoint_token_hash', $endpointTokenHash)
                ->orWhereHas('configValues', fn (Builder $query): Builder => $query
                    ->where('key', DrNetworkConfigValue::normalizeKey('webhook_endpoint_token'))
                    ->where('lookup_hash', $endpointTokenHash)
                )
            )
            ->with('configValues')
            ->first();

        if (! $network) {
            throw new RuntimeException('Unknown doctor network webhook endpoint.');
        }

        if (! ($network->feature_flags['webhook_enabled'] ?? true)) {
            throw new RuntimeException('Doctor network webhook endpoint is disabled.');
        }

        return $network;
    }
}
