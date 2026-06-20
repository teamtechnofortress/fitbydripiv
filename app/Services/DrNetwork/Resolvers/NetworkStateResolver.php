<?php

namespace App\Services\DrNetwork\Resolvers;

use App\Models\DrNetwork;
use App\Models\NetworkStateMapping;
use App\Models\State;

class NetworkStateResolver
{
    public function resolve(string $stateCode): ?array
    {
        $state = State::query()
            ->active()
            ->forCode($stateCode)
            ->first();

        if (! $state) {
            return null;
        }

        $mapping = NetworkStateMapping::query()
            ->forState($state->id)
            ->active()
            ->ordered()
            ->with(['drNetwork', 'flowDefinition'])
            ->first();

        if (! $mapping || ! $mapping->drNetwork || $mapping->drNetwork->status !== DrNetwork::STATUS_ACTIVE) {
            return null;
        }

        return [
            'state' => $state,
            'network' => $mapping->drNetwork,
            'flow' => $mapping->flowDefinition,
        ];
    }
}
