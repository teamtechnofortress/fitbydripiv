<?php

namespace App\Services\DrNetwork\Adapters\OlaHealth\Webhooks;

use App\Models\DrNetwork;
use Illuminate\Http\Request;
use RuntimeException;

class OlaHealthWebhookVerifier
{
    public function verify(Request $request, DrNetwork $network): void
    {
        $signaturesEnabled = (bool) $network->configValue('webhook_signatures_enabled', false);

        if (! $signaturesEnabled) {
            return;
        }

        $secret = $network->configValue('webhook_secret');
        $headerName = $network->configValue('webhook_signature_header');
        $algorithm = $network->configValue('webhook_signature_algorithm', 'sha256');

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
