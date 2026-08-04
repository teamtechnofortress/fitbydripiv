<?php

namespace App\Services\DrNetwork\Adapters\OlaHealth;

use App\Models\DrNetwork;
use App\Services\DrNetwork\Adapters\BaseNetworkAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

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
        $network = DrNetwork::query()
            ->where('adapter_key', $this->adapterKey)
            ->with('configValues')
            ->first();

        if ($network) {
            $this->configureForNetwork($network);

            return;
        }

        $this->network = null;
        $this->baseUrl = rtrim((string) config('services.ola_health.base_url', ''), '/');
        $this->authToken = config('services.ola_health.auth_token');
        $this->secretToken = config('services.ola_health.secret_token');
        $this->tenant = config('services.ola_health.tenant');
    }

    public function configureForNetwork(DrNetwork $network): void
    {
        $this->network = $network->relationLoaded('configValues')
            ? $network
            : $network->load('configValues');

        $settings = $this->network?->settings ?? [];

        $this->baseUrl = rtrim(
            (string) ($settings['api_base_url']
                ?? config('services.ola_health.base_url', '')),
            '/'
        );
        $this->authToken = $this->network?->configValue('auth_token', config('services.ola_health.auth_token'));
        $this->secretToken = $this->network?->configValue('secret_token', config('services.ola_health.secret_token'));
        $this->tenant = $this->network?->configValue('tenant', config('services.ola_health.tenant'));
    }

    public function submitCase(array $payload): array
    {
        $payload = $this->withSubmissionDefaults($payload);
        $url = "{$this->requiredBaseUrl()}/api-v2/telehealth/service/new-schedule-request";

        Log::channel('dr_network')->info('Ola Health case submission payload.', [
            'adapter_key' => $this->adapterKey,
            'dr_network_id' => $this->network?->id,
            'endpoint' => $url,
            'payload' => $payload,
        ]);

        $response = $this->authenticatedMultipartPost($url, $payload);

        return [
            'case_id' => $response['data'] ?? $response['order_guid'] ?? null,
            'status' => ($response['success'] ?? false) ? 'submitted' : 'submission_failed',
            'raw' => $response,
        ];
    }

    public function getCaseStatus(string $networkCaseId): array
    {
        $response = $this->authenticatedTelehealthGet(
            "{$this->requiredBaseUrl()}/api-v2/telehealth/service/orders/{$networkCaseId}",
            []
        );

        return [
            'case_id' => $networkCaseId,
            'network_status' => $response['result']['status'] ?? 'unknown',
            'raw' => $response,
        ];
    }

    public function getAvailableSlots(array $params = []): array
    {
        $requestParams = array_filter($params, fn ($value) => $value !== null && $value !== '');
        $stateCode = strtoupper((string) ($requestParams['state_code'] ?? ''));
        $serviceIdentifier = $requestParams['service_id']
            ?? $requestParams['external_service_id']
            ?? $requestParams['network_product_identifier']
            ?? null;

        if ($stateCode === '') {
            throw new RuntimeException('Ola Health provider schedules require state_code.');
        }

        if (! filled($serviceIdentifier)) {
            throw new RuntimeException('Ola Health provider schedules require service_id.');
        }

        $queryParams = $requestParams;
        unset(
            $queryParams['state_code'],
            $queryParams['service_id'],
            $queryParams['service_key'],
            $queryParams['session_type'],
            $queryParams['network_product_identifier'],
            $queryParams['external_service_id'],
            $queryParams['tenant']
        );

        $endpoint = sprintf(
            '%s/api-v2/telehealth/schedules/get-provider-schedules/%s/service/%s',
            $this->requiredBaseUrl(),
            rawurlencode($stateCode),
            rawurlencode((string) $serviceIdentifier)
        );

        $this->logTokenEvent('Fetching Ola Health provider schedules.', [
            'endpoint' => $endpoint,
            'state_code' => $stateCode,
            'service_identifier' => (string) $serviceIdentifier,
            'query_param_keys' => array_keys($queryParams),
            'network_product_identifier_present' => isset($requestParams['network_product_identifier']),
        ]);

        $response = $this->authenticatedProviderScheduleGet(
            $endpoint,
            $queryParams
        );

        return $response['data'] ?? [];
    }

    public function bookSlot(string $slotId, array $params = []): array
    {
        $providerGuid = $params['provider_guid'] ?? $params['provider_id'] ?? $slotId;
        $scheduledTime = $params['schedule_start_date'] ?? $params['start_datetime'] ?? $params['scheduled_time'] ?? null;
        $scheduleEnd = $this->scheduleEndDate(
            $scheduledTime,
            $params['schedule_end_date'] ?? $params['end_datetime'] ?? null,
            $params['appt_length'] ?? null
        );

        if (! filled($scheduledTime)) {
            throw new RuntimeException('Ola Health slot selection requires schedule_start_date.');
        }

        if (! filled($scheduleEnd)) {
            throw new RuntimeException('Ola Health slot selection requires schedule_end_date.');
        }

        if (! filled($providerGuid)) {
            throw new RuntimeException('Ola Health slot selection requires provider_guid.');
        }

        $this->logTokenEvent('Ola Health slot selected locally; schedule is submitted in new-schedule-request.', [
            'slot_id' => $slotId,
            'provider_guid' => $providerGuid,
            'schedule_start_date' => $scheduledTime,
            'schedule_end_date' => $scheduleEnd,
        ]);

        return [
            'slot_id' => $slotId,
            'provider_guid' => $providerGuid,
            'provider_id' => $providerGuid,
            'scheduled_time' => $scheduledTime,
            'schedule_start_date' => $scheduledTime,
            'schedule_end_date' => $scheduleEnd,
            'status' => 'selected',
        ];
    }

    public function translateStatus(string $networkStatus): string
    {
        return OlaHealthStatusMapper::toInternal($networkStatus);
    }

    public function testAuthentication(): array
    {
        $token = $this->getAccessToken();

        return [
            'ok' => true,
            'adapter_key' => $this->adapterKey,
            'base_url' => $this->baseUrl,
            'tenant_present' => filled($this->tenant),
            'access_token_fingerprint' => $this->fingerprint($token),
        ];
    }

    private function getAccessToken(): string
    {
        $cacheKey = $this->accessTokenCacheKey();
        $cachedToken = Cache::get($cacheKey);

        if (is_string($cachedToken) && $cachedToken !== '') {
            $this->logTokenEvent('Cached Ola Health access token found.', [
                'cache_key_hash' => hash('sha256', $cacheKey),
                'access_token_fingerprint' => $this->fingerprint($cachedToken),
            ]);

            if ($this->validateAccessToken($cachedToken)) {
                $this->logTokenEvent('Cached Ola Health access token is valid.', [
                    'cache_key_hash' => hash('sha256', $cacheKey),
                    'access_token_fingerprint' => $this->fingerprint($cachedToken),
                ]);

                return $cachedToken;
            }

            Cache::forget($cacheKey);

            $this->logTokenEvent('Cached Ola Health access token is invalid; cache entry cleared.', [
                'cache_key_hash' => hash('sha256', $cacheKey),
            ], 'warning');
        }

        $token = $this->requestAccessToken();

        Cache::put($cacheKey, $token, now()->addMinutes(25));

        $this->logTokenEvent('Fresh Ola Health access token cached.', [
            'cache_key_hash' => hash('sha256', $cacheKey),
            'access_token_fingerprint' => $this->fingerprint($token),
            'ttl_minutes' => 25,
        ]);

        return $token;
    }

    private function requestAccessToken(): string
    {
        if (! $this->authToken || ! $this->secretToken) {
            $this->logTokenEvent('Ola Health credentials missing before tenant login.', [
                ...$this->credentialDiagnostics(),
            ], 'error');

            throw new RuntimeException('Ola Health credentials are not configured.');
        }

        $this->logTokenEvent('Requesting fresh Ola Health access token.', [
            'endpoint' => "{$this->requiredBaseUrl()}/auth/tennant/login",
            ...$this->credentialDiagnostics(),
        ]);

        $response = $this->post(
            "{$this->requiredBaseUrl()}/auth/tennant/login",
            [
                'auth_token' => $this->authToken,
                'secret_token' => $this->secretToken,
            ],
            $this->tenantLoginHeaders()
        );

        $token = $response['token'] ?? null;

        if (! is_string($token) || $token === '') {
            $this->logTokenEvent('Ola Health tenant login response did not include token.', [
                'response_keys' => array_keys($response),
                'success' => $response['success'] ?? null,
                'message' => $response['message'] ?? null,
            ], 'error');

            throw new RuntimeException('Ola Health: token missing from tenant login response.');
        }

        $this->logTokenEvent('Ola Health tenant login returned access token.', [
            'response_keys' => array_keys($response),
            'success' => $response['success'] ?? null,
            'message' => $response['message'] ?? null,
            'access_token_fingerprint' => $this->fingerprint($token),
        ]);

        return $token;
    }

    private function validateAccessToken(string $token): bool
    {
        $endpoint = "{$this->requiredBaseUrl()}/api-v2/validate-token";

        $this->logTokenEvent('Validating cached Ola Health access token.', [
            'endpoint' => $endpoint,
        ]);

        try {
            $response = Http::withHeaders($this->accessTokenValidationHeaders($token))
                ->timeout(15)
                ->get($endpoint);

            $json = $response->json();

            $this->logTokenEvent('Ola Health access token validation response received.', [
                'status' => $response->status(),
                'response_keys' => is_array($json) ? array_keys($json) : [],
                'message' => is_array($json) ? ($json['message'] ?? null) : null,
                'access_token_fingerprint' => $this->fingerprint($token),
            ], $response->successful() ? 'info' : 'warning');

            if ($response->failed() || ! is_array($json)) {
                return false;
            }

            return ($json['status'] ?? $json['success'] ?? false) === true;
        } catch (Throwable $e) {
            $this->logTokenEvent('Ola Health access token validation failed before response.', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ], 'warning');

            return false;
        }
    }

    private function accessTokenCacheKey(): string
    {
        return 'ola_health_tenant_login_token_'.md5(implode('|', [
            'tenant-login-v1',
            $this->baseUrl,
            (string) $this->tenant,
            (string) $this->authToken,
            (string) $this->secretToken,
        ]));
    }

    private function authenticatedGet(string $url, array $params = []): array
    {
        return $this->withFreshTokenRetry(
            'GET',
            $url,
            fn (string $token): array => $this->get($url, $params, $this->authHeaders($token))
        );
    }

    private function authenticatedProviderScheduleGet(string $url, array $params = []): array
    {
        return $this->withFreshTokenRetry(
            'GET',
            $url,
            fn (string $token): array => $this->get($url, $params, $this->providerScheduleHeaders($token))
        );
    }

    private function authenticatedTelehealthGet(string $url, array $params = []): array
    {
        return $this->withFreshTokenRetry(
            'GET',
            $url,
            fn (string $token): array => $this->get($url, $params, $this->telehealthMultipartHeaders($token))
        );
    }

    private function authenticatedPost(string $url, array $payload = []): array
    {
        return $this->withFreshTokenRetry(
            'POST',
            $url,
            fn (string $token): array => $this->post($url, $payload, $this->authHeaders($token))
        );
    }

    private function authenticatedMultipartPost(string $url, array $payload = []): array
    {
        return $this->withFreshTokenRetry(
            'POST',
            $url,
            fn (string $token): array => $this->multipartPost($url, $payload, $token)
        );
    }

    private function multipartPost(string $url, array $payload, string $token): array
    {
        $fields = $this->multipartFields($payload);
        $files = $payload['files'] ?? [];
        $request = Http::withHeaders($this->multipartPostHeaders($token))
            ->timeout(60)
            ->asMultipart();

        foreach (array_values($files) as $index => $file) {
            $path = $file['path'] ?? null;

            if (! is_string($path) || ! Storage::exists($path)) {
                continue;
            }

            $request->attach(
                'file_'.($index + 1),
                Storage::get($path),
                $file['file_name'] ?? basename($path),
                array_filter(['Content-Type' => $file['content_type'] ?? null])
            );
        }

        $response = $request->post($url, $fields);
        $json = $response->json();

        Log::channel('dr_network')->info(sprintf('[%s] POST %s', $this->adapterKey, $url), [
            'status' => $response->status(),
            'request_summary' => array_keys($fields),
            'file_count' => count($files),
            'response_keys' => is_array($json) ? array_keys($json) : [],
        ]);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                '[%s] POST %s failed with status %d: %s',
                $this->adapterKey,
                $url,
                $response->status(),
                $response->body()
            ));
        }

        return is_array($json) ? $json : [];
    }

    private function withFreshTokenRetry(string $method, string $url, callable $request): array
    {
        $token = $this->getAccessToken();

        $this->logTokenEvent('Calling Ola Health API with access token.', [
            'method' => $method,
            'endpoint' => $url,
            'access_token_fingerprint' => $this->fingerprint($token),
        ]);

        try {
            return $request($token);
        } catch (RuntimeException $e) {
            if (! $this->isTokenExpiredError($e)) {
                throw $e;
            }

            $this->clearAccessTokenCache('Ola Health API reported token expired.', [
                'method' => $method,
                'endpoint' => $url,
                'access_token_fingerprint' => $this->fingerprint($token),
                'exception_message' => $e->getMessage(),
            ]);

            $freshToken = $this->requestAccessToken();
            Cache::put($this->accessTokenCacheKey(), $freshToken, now()->addMinutes(25));

            $this->logTokenEvent('Retrying Ola Health API call with refreshed access token.', [
                'method' => $method,
                'endpoint' => $url,
                'access_token_fingerprint' => $this->fingerprint($freshToken),
            ]);

            return $request($freshToken);
        }
    }

    private function clearAccessTokenCache(string $reason, array $context = []): void
    {
        $cacheKey = $this->accessTokenCacheKey();

        Cache::forget($cacheKey);

        $this->logTokenEvent($reason, array_merge([
            'cache_key_hash' => hash('sha256', $cacheKey),
        ], $context), 'warning');
    }

    private function isTokenExpiredError(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'status 401')
            || str_contains($message, 'token expired')
            || str_contains($message, 'token is expired');
    }

    private function tenantLoginHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function accessTokenValidationHeaders(string $token): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Access-Token' => $token,
        ];
    }

    private function providerScheduleHeaders(string $token): array
    {
        return $this->telehealthMultipartHeaders($token);
    }

    private function telehealthMultipartHeaders(string $token): array
    {
        return [
            'Content-Type' => 'multipart/form-data',
            'Accept' => 'application/json',
            'X-Access-Token' => $token,
        ];
    }

    private function multipartPostHeaders(string $token): array
    {
        return [
            'Accept' => 'application/json',
            'X-Access-Token' => $token,
        ];
    }

    private function authHeaders(string $token): array
    {
        return array_filter([
            'X-Access-Token' => $token,
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function logTokenEvent(string $message, array $context = [], string $level = 'info'): void
    {
        Log::channel('dr_network')->{$level}($message, array_merge([
            'adapter_key' => $this->adapterKey,
            'dr_network_id' => $this->network?->id,
            'credential_source' => $this->network ? 'dr_network_config_values' : 'services_config',
            'base_url' => $this->baseUrl,
            'tenant' => $this->tenant,
        ], $context));
    }

    private function credentialDiagnostics(): array
    {
        return [
            'auth_token_present' => filled($this->authToken),
            'secret_token_present' => filled($this->secretToken),
            'tenant_present' => filled($this->tenant),
            'auth_token_fingerprint' => $this->fingerprint($this->authToken),
            'secret_token_fingerprint' => $this->fingerprint($this->secretToken),
        ];
    }

    private function fingerprint(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        return substr(hash('sha256', $value), 0, 12);
    }

    private function withSubmissionDefaults(array $payload): array
    {
        $payload['user_data']['tennant'] ??= $this->tenant;
        $payload['identifier']['tennant'] ??= $this->tenant;
        $payload['identifier']['sessionType'] ??= 'initial';

        return $payload;
    }

    private function multipartFields(array $payload): array
    {
        $this->assertValidSubmissionSchedule(
            $payload['schedule'] ?? null,
            (bool) ($payload['schedule_required'] ?? false)
        );

        $fields = [
            'transaction_id' => (string) ($payload['transaction_id'] ?? ''),
        ];

        foreach ([
            'user_data',
            'address',
            'service_data',
            'identifier',
            'pharmacyDetails',
            'schedule',
            'user_insurance',
        ] as $key) {
            if (! array_key_exists($key, $payload) || $payload[$key] === null || $payload[$key] === []) {
                continue;
            }

            $fields[$key] = is_array($payload[$key])
                ? json_encode($payload[$key], JSON_THROW_ON_ERROR)
                : (string) $payload[$key];
        }

        return array_filter($fields, fn (string $value): bool => $value !== '');
    }

    private function assertValidSubmissionSchedule(mixed $schedule, bool $scheduleRequired): void
    {
        if (! is_array($schedule)) {
            if (! $scheduleRequired) {
                return;
            }

            throw new RuntimeException('Ola Health submission requires schedule data.');
        }

        foreach (['schedule_start_date', 'schedule_end_date', 'provider_guid'] as $key) {
            if (! filled($schedule[$key] ?? null)) {
                throw new RuntimeException("Ola Health submission schedule is missing {$key}.");
            }
        }
    }

    private function scheduleEndDate(?string $startDate, ?string $endDate, mixed $appointmentLength): ?string
    {
        if (filled($endDate)) {
            return $endDate;
        }

        if (! filled($startDate)) {
            return null;
        }

        $minutes = is_numeric($appointmentLength) ? max(1, (int) $appointmentLength) : 15;

        return Carbon::parse($startDate)->addMinutes($minutes)->toJSON();
    }

    private function requiredBaseUrl(): string
    {
        if ($this->baseUrl === '') {
            throw new RuntimeException('Ola Health API base URL is not configured.');
        }

        return $this->baseUrl;
    }
}
