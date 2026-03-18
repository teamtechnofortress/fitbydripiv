<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StripeWebhookEvent;
use Illuminate\Http\JsonResponse;

class WebhookAdminController extends Controller
{
    public function show(StripeWebhookEvent $webhook): JsonResponse
    {
        return response()->json([
            'id' => $webhook->id,
            'stripe_event_id' => $webhook->stripe_event_id,
            'event_type' => $webhook->event_type,
            'processed' => $webhook->processed,
            'processed_at' => $webhook->processed_at,
            'webhookable_type' => $webhook->webhookable_type,
            'webhookable_id' => $webhook->webhookable_id,
            'payload' => $webhook->payload_json,
            'created_at' => $webhook->created_at,
            'updated_at' => $webhook->updated_at,
        ]);
    }
}
