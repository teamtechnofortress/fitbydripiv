<?php

namespace App\Http\Controllers\DrNetwork;

use App\Http\Controllers\Controller;
use App\Models\DrNetwork;
use App\Services\DrNetwork\Webhooks\NetworkWebhookReceiver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NetworkWebhookController extends Controller
{
    public function __construct(
        private NetworkWebhookReceiver $receiver,
    ) {}

    public function handle(Request $request, string $endpointToken): JsonResponse
    {
        /** @var DrNetwork|null $network */
        $network = $request->attributes->get('drNetwork');

        if (! $network) {
            abort(404);
        }

        $event = $this->receiver->receive($request, $network);

        return response()->json([
            'event_id' => $event->id,
            'status' => $event->status,
        ], 202);
    }
}
