<?php

namespace App\Services\DrNetwork\Adapters\OlaHealth;

use App\Models\DrNetwork;
use App\Services\DrNetwork\Adapters\BaseNetworkAdapter;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class OlaHealthAdapter extends BaseNetworkAdapter
{
    protected string $adapterKey = 'ola_health';

    private ?DrNetwork $network;

    private string $baseUrl;

    private ?string $authToken;

    private ?string $secretToken;

    private ?string $tenant;

    public function __construct()
    {
        $this->network = DrNetwork::query()
            ->where('adapter_key', $this->adapterKey)
            ->with('configValues')
            ->first();

        $settings = $this->network?->settings ?? [];

        $this->baseUrl = rtrim(
            (string) ($this->network?->configValue('api_base_url')
                ?? $settings['api_base_url']
                ?? config('services.ola_health.base_url', '')),
            '/'
        );
        $this->authToken = $this->network?->configValue('auth_token', config('services.ola_health.auth_token'));
        $this->secretToken = $this->network?->configValue('secret_token', config('services.ola_health.secret_token'));
        $this->tenant = $this->network?->configValue('tenant', config('services.ola_health.tenant'));
    }

    public function submitCase(array $payload): array
    {
        $response = $this->post(
            "{$this->requiredBaseUrl()}/api/v1/schedule/request",
            $payload,
            $this->authHeaders($this->getAccessToken())
        );

        return [
            'case_id' => $response['data']['schedule_id'] ?? $response['data']['id'] ?? null,
            'status' => $response['data']['status'] ?? 'submitted',
            'raw' => $response,
        ];
    }

    public function getCaseStatus(string $networkCaseId): array
    {
        $response = $this->get(
            "{$this->requiredBaseUrl()}/api/v1/schedule/{$networkCaseId}",
            [],
            $this->authHeaders($this->getAccessToken())
        );

        return [
            'case_id' => $networkCaseId,
            'network_status' => $response['data']['status'] ?? 'unknown',
            'raw' => $response,
        ];
    }

    public function getAvailableSlots(array $params = []): array
    {
        $response = $this->get(
            "{$this->requiredBaseUrl()}/api/v1/provider/schedules",
            array_filter(array_merge([
                'tenant' => $this->tenant,
                'service_id' => $this->network?->configValue('service_id'),
                'service_key' => $this->network?->configValue('service_key'),
                'session_type' => $this->network?->configValue('session_type'),
            ], $params), fn ($value) => $value !== null && $value !== ''),
            $this->authHeaders($this->getAccessToken())
        );

        return $response['data'] ?? [];
    }

    public function bookSlot(string $slotId, array $params = []): array
    {
        $response = $this->post(
            "{$this->requiredBaseUrl()}/api/v1/provider/schedules/{$slotId}/book",
            $params,
            $this->authHeaders($this->getAccessToken())
        );

        return $response['data'] ?? $response;
    }

    public function translateStatus(string $networkStatus): string
    {
        return OlaHealthStatusMapper::toInternal($networkStatus);
    }

    private function getAccessToken(): string
    {
        $cacheKey = 'ola_health_token_'.md5((string) ($this->tenant ?? $this->authToken));

        return Cache::remember($cacheKey, now()->addMinutes(25), function (): string {
            if (! $this->authToken || ! $this->secretToken) {
                throw new RuntimeException('Ola Health credentials are not configured.');
            }

            $response = $this->post(
                "{$this->requiredBaseUrl()}/api/v1/auth/token",
                array_filter([
                    'auth_token' => $this->authToken,
                    'secret_token' => $this->secretToken,
                    'tenant' => $this->tenant,
                ], fn ($value) => $value !== null && $value !== '')
            );

            return $response['data']['access_token']
                ?? throw new RuntimeException('Ola Health: access_token missing from auth response.');
        });
    }

    private function authHeaders(string $token): array
    {
        return array_filter([
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function requiredBaseUrl(): string
    {
        if ($this->baseUrl === '') {
            throw new RuntimeException('Ola Health API base URL is not configured.');
        }

        return $this->baseUrl;
    }
}
