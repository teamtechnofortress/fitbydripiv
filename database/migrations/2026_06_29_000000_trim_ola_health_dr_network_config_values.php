<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ALLOWED_KEYS = [
        'auth_token',
        'secret_token',
        'tenant',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('dr_networks') || ! Schema::hasTable('dr_network_config_values')) {
            return;
        }

        $network = DB::table('dr_networks')
            ->where('slug', 'ola-health')
            ->first();

        if (! $network) {
            return;
        }

        $configValues = DB::table('dr_network_config_values')
            ->where('dr_network_id', $network->id)
            ->get()
            ->keyBy('key');

        $settings = $this->decodeJson($network->settings);

        if (! isset($settings['api_base_url'])) {
            $settings['api_base_url'] = $this->decryptedValue($configValues->get('api_base_url')?->value)
                ?? env('OLA_HEALTH_API_URL', 'https://dev-api.ola-digital-int.com');
        }

        $webhookToken = env('OLA_HEALTH_WEBHOOK_ENDPOINT_TOKEN')
            ?: $this->decryptedValue($configValues->get('webhook_endpoint_token')?->value);

        if ($webhookToken && ! isset($settings['webhook_endpoint_token_hash'])) {
            $settings['webhook_endpoint_token_hash'] = hash('sha256', trim((string) $webhookToken));
        }

        if (! isset($settings['webhook_signatures_enabled'])) {
            $settings['webhook_signatures_enabled'] = $this->boolValue(
                $this->decryptedValue($configValues->get('webhook_signatures_enabled')?->value),
                false
            );
        }

        DB::table('dr_networks')
            ->where('id', $network->id)
            ->update([
                'settings' => json_encode($settings, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);

        DB::table('dr_network_config_values')
            ->where('dr_network_id', $network->id)
            ->whereNotIn('key', self::ALLOWED_KEYS)
            ->delete();
    }

    public function down(): void
    {
        //
    }

    private function decodeJson(?string $json): array
    {
        if (! $json) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function decryptedValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function boolValue(?string $value, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
};
