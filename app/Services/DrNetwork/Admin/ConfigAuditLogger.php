<?php

namespace App\Services\DrNetwork\Admin;

use App\Models\NetworkConfigAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ConfigAuditLogger
{
    public function log(Model $model, string $action, ?array $before = null, ?array $after = null, ?User $actor = null): void
    {
        $actor ??= Auth::user();

        if (! $actor) {
            return;
        }

        NetworkConfigAuditLog::query()->create([
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'action' => $action,
            'before' => $this->redactSecrets($before),
            'after' => $this->redactSecrets($after),
            'actor_id' => $actor->id,
            'actor_role' => $actor->role ?? null,
        ]);
    }

    private function redactSecrets(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        foreach (['value', 'auth_token', 'secret_token', 'webhook_endpoint_token'] as $key) {
            if (array_key_exists($key, $payload)) {
                $payload[$key] = '[redacted]';
            }
        }

        return $payload;
    }
}
