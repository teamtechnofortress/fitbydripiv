<?php

namespace App\Services\DrNetwork\Webhooks\Contracts;

use App\Models\DrNetwork;
use App\Services\DrNetwork\Webhooks\DTO\NormalizedNetworkWebhook;
use Illuminate\Http\Request;

interface NetworkWebhookAdapter
{
    public function verify(Request $request, DrNetwork $network): void;

    public function normalize(Request $request, DrNetwork $network): NormalizedNetworkWebhook;
}
