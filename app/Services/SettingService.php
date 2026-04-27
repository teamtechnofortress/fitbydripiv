<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SettingService
{
    public function all(?string $group = null): Collection
    {
        $this->ensureDefaults();

        return Setting::query()
            ->when($group, fn ($query) => $query->where('group', $group))
            ->orderBy('group')
            ->orderBy('key')
            ->get();
    }

    public function save(array $data): Setting
    {
        $this->ensureDefaults();

        $definition = $this->definition($data['key']);

        if ($definition === null) {
            throw ValidationException::withMessages([
                'key' => 'Unknown setting key.',
            ]);
        }

        $setting = isset($data['id'])
            ? Setting::findOrFail($data['id'])
            : Setting::firstOrNew(['key' => $data['key']]);

        $setting->fill([
            'key' => $data['key'],
            'value' => $data['value'] ?? $definition['default'],
            'type' => $definition['type'],
            'group' => $this->groupFor($data['key']) ?? 'general',
        ]);
        $setting->save();

        return $setting->fresh();
    }

    public function value(string $key, mixed $default = null): mixed
    {
        $this->ensureDefaults();

        $setting = Setting::query()->where('key', $key)->first();

        if ($setting) {
            return $setting->value;
        }

        $definition = $this->definition($key);

        return $definition['default'] ?? $default;
    }

    public function ensureDefaults(): void
    {
        foreach ($this->definitions() as $group => $settings) {
            foreach ($settings as $key => $definition) {
                $setting = Setting::firstOrCreate(
                    ['key' => $key],
                    [
                        'value' => $definition['default'] ?? null,
                        'type' => $definition['type'] ?? 'string',
                        'group' => $group,
                    ]
                );

                $expectedType = $definition['type'] ?? 'string';

                if ($setting->type !== $expectedType || $setting->group !== $group) {
                    $setting->fill([
                        'type' => $expectedType,
                        'group' => $group,
                    ]);
                    $setting->save();
                }
            }
        }
    }

    public function allowedKeys(): array
    {
        return collect($this->definitions())
            ->flatMap(fn (array $settings) => array_keys($settings))
            ->values()
            ->all();
    }

    protected function definitions(): array
    {
        return config('settings', []);
    }

    protected function definition(string $key): ?array
    {
        foreach ($this->definitions() as $group => $settings) {
            if (array_key_exists($key, $settings)) {
                return $settings[$key];
            }
        }

        return null;
    }

    protected function groupFor(string $key): ?string
    {
        foreach ($this->definitions() as $group => $settings) {
            if (array_key_exists($key, $settings)) {
                return $group;
            }
        }

        return null;
    }
}
