<?php

namespace App\Services;

use App\Models\State;

class StateCodeResolver
{
    public function resolve(?string $state, string $countryCode = 'US'): ?string
    {
        $state = trim((string) $state);

        if ($state === '') {
            return null;
        }

        return State::query()
            ->active()
            ->matchingState($state, $countryCode)
            ->value('state_code');
    }
}
