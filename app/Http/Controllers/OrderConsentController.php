<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderConsentRequest;
use App\Models\Order;
use App\Models\OrderConsent;
use App\Services\Consent\ConsentBlockingRuleEvaluator;
use App\Services\Consent\OrderConsentService;
use Illuminate\Http\JsonResponse;

class OrderConsentController extends Controller
{
    public function __construct(
        private OrderConsentService $consentService,
        private ConsentBlockingRuleEvaluator $blockingRuleEvaluator,
    ) {}

    public function store(StoreOrderConsentRequest $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($order);

        $consent = $this->consentService->record($order, $request->validated(), $request);
        $decision = $consent->accepted ? 'accepted' : 'rejected';
        $blockingRule = $consent->accepted ? null : $this->blockingRuleEvaluator->termsConsentRejectionRule();

        $payload = [
            'submitted' => true,
            'decision' => $decision,
            'accepted' => $consent->accepted,
            'hard_stop' => ! $consent->accepted,
            'code' => $consent->accepted ? 'terms_consent_accepted' : 'terms_consent_rejected',
            'message' => $consent->accepted
                ? 'Terms consent accepted.'
                : 'Terms consent rejected.',
            'journey_url' => "/api/v1/orders/{$order->order_uuid}/journey",
            'consent' => $this->payload($consent),
        ];

        if ($blockingRule) {
            $payload['rule_key'] = $blockingRule['rule_key'];
            $payload['reason'] = $blockingRule['reason'];
            $payload['hard_stop_type'] = $blockingRule['hard_stop_type'];
            $payload['conditions'] = $blockingRule['conditions'];
            $payload['blocking_rule'] = $blockingRule;
        }

        return response()->json($payload);
    }

    private function payload(OrderConsent $consent): array
    {
        return [
            'id' => $consent->id,
            'order_id' => $consent->order_id,
            'patient_id' => $consent->patient_id,
            'consultation_record_id' => $consent->consultation_record_id,
            'consent_key' => $consent->consent_key,
            'consent_title' => $consent->consent_title,
            'content_version' => $consent->content_version,
            'content_hash' => $consent->content_hash,
            'accepted' => $consent->accepted,
            'accepted_at' => $consent->accepted_at,
            'rejected_at' => $consent->rejected_at,
            'created_at' => $consent->created_at,
            'updated_at' => $consent->updated_at,
        ];
    }

    private function authorizeOrder(Order $order): void {}
}
