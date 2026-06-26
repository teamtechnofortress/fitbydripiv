<?php

namespace App\Services\DrNetwork\Adapters\OlaHealth\Webhooks;

use App\Models\DrNetwork;
use App\Services\DrNetwork\Webhooks\Contracts\NetworkWebhookAdapter;
use App\Services\DrNetwork\Webhooks\DTO\NormalizedNetworkWebhook;
use Illuminate\Http\Request;

class OlaHealthWebhookAdapter implements NetworkWebhookAdapter
{
    public function __construct(
        private OlaHealthWebhookVerifier $verifier,
        private OlaHealthWebhookParser $parser,
    ) {}

    public function verify(Request $request, DrNetwork $network): void
    {
        $this->verifier->verify($request, $network);
    }

    public function normalize(Request $request, DrNetwork $network): NormalizedNetworkWebhook
    {
        return $this->parser->normalize($request, $network);
    }
}
