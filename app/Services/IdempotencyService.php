<?php

namespace App\Services;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class IdempotencyService
{
    public function handle(?string $key, string $endpoint, array $payload, Closure $callback): array
    {
        if (! $key) {
            return $callback();
        }

        $hash = sha1(json_encode($payload));

        return DB::transaction(function () use ($key, $endpoint, $hash, $payload, $callback) {
            $record = IdempotencyKey::where('key', $key)->lockForUpdate()->first();

            if ($record) {
                if ($record->request_hash !== $hash || $record->endpoint !== $endpoint) {
                    throw new ConflictHttpException('Idempotency key already used with different payload.');
                }

                if ($record->response_payload !== null) {
                    return $record->response_payload;
                }
            } else {
                $record = IdempotencyKey::create([
                    'key' => $key,
                    'endpoint' => $endpoint,
                    'request_hash' => $hash,
                    'status' => 'pending',
                ]);
            }

            $response = $callback();

            $record->update([
                'response_payload' => $response,
                'status' => 'completed',
            ]);

            return $response;
        });
    }
}
