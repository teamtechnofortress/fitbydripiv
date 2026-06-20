<?php

namespace App\Services\DrNetwork\Flow;

use Illuminate\Support\Collection;

class FlowStepSequence
{
    public static function first(array $steps): ?array
    {
        return self::ordered($steps)->first();
    }

    public static function next(array $steps, string $currentStepKey): ?array
    {
        $ordered = self::ordered($steps);
        $index = $ordered->search(fn (array $step): bool => $step['step_key'] === $currentStepKey);

        if ($index === false) {
            return null;
        }

        return $ordered->get($index + 1);
    }

    private static function ordered(array $steps): Collection
    {
        return collect($steps)
            ->sortBy(fn (array $step): int => (int) ($step['order'] ?? 0))
            ->values();
    }
}
