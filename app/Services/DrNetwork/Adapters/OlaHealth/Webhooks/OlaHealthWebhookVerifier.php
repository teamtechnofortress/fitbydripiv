<?php

namespace App\Services\DrNetwork\Adapters\OlaHealth\Webhooks;

use App\Models\DrNetwork;
use Illuminate\Http\Request;
use RuntimeException;

class OlaHealthWebhookVerifier
{
    public function verify(Request $request, DrNetwork $network): void
    {
        $settings = $network->settings ?? [];
        $signaturesEnabled = (bool) ($settings['webhook_signatures_enabled'] ?? false);

        if (! $signaturesEnabled) {
            return;
        }

        $secret = $settings['webhook_secret'] ?? null;
        $headerName = $settings['webhook_signature_header'] ?? null;
        $algorithm = $settings['webhook_signature_algorithm'] ?? 'sha256';

        if (! $secret || ! $headerName) {
            throw new RuntimeException('Ola webhook signature verification is enabled but not fully configured.');
        }

        $signature = (string) $request->header($headerName, '');

        if ($signature === '') {
            throw new RuntimeException('Missing Ola webhook signature.');
        }

        $expected = hash_hmac((string) $algorithm, $request->getContent(), (string) $secret);
        $normalizedSignature = str_starts_with($signature, "{$algorithm}=")
            ? substr($signature, strlen((string) $algorithm) + 1)
            : $signature;

        if (! hash_equals($expected, $normalizedSignature)) {
            throw new RuntimeException('Invalid Ola webhook signature.');
        }
    }
}
