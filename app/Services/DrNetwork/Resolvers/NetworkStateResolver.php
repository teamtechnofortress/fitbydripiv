<?php

namespace App\Services\DrNetwork\Resolvers;

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
            ->whereHas('drNetwork', fn ($query) => $query->active())
            ->whereHas('flowDefinition', fn ($query) => $query->active())
            ->ordered()
            ->with(['drNetwork', 'flowDefinition'])
            ->first();

        if (! $mapping) {
            return null;
        }

        return [
            'state' => $state,
            'network' => $mapping->drNetwork,
            'flow' => $mapping->flowDefinition,
        ];
    }
}
