<?php

namespace App\Services\DrNetwork\Adapters;

use App\Services\DrNetwork\Adapters\Contracts\DoctorNetworkAdapter;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

abstract class BaseNetworkAdapter implements DoctorNetworkAdapter
{
    protected string $adapterKey = '';

    public function getAdapterKey(): string
    {
        return $this->adapterKey;
    }

    protected function post(string $url, array $payload, array $headers = []): array
    {
        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->post($url, $payload);

        $this->logApiCall('POST', $url, $payload, $response);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                '[%s] POST %s failed with status %d: %s',
                $this->adapterKey,
                $url,
                $response->status(),
                $response->body()
            ));
        }

        return $this->jsonArray($response);
    }

    protected function get(string $url, array $params = [], array $headers = []): array
    {
        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->get($url, $params);

        $this->logApiCall('GET', $url, $params, $response);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                '[%s] GET %s failed with status %d: %s',
                $this->adapterKey,
                $url,
                $response->status(),
                $response->body()
            ));
        }

        return $this->jsonArray($response);
    }

    private function logApiCall(string $method, string $url, array $request, Response $response): void
    {
        $responseBody = $response->json();

        Log::channel('dr_network')->info(sprintf('[%s] %s %s', $this->adapterKey, $method, $url), [
            'status' => $response->status(),
            'request_summary' => array_keys($request),
            'response_keys' => is_array($responseBody) ? array_keys($responseBody) : [],
        ]);
    }

    private function jsonArray(Response $response): array
    {
        $json = $response->json();

        return is_array($json) ? $json : [];
    }
}
