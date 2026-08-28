<?php

namespace App\Services\Consent;

use App\Models\Order;
use App\Models\OrderConsent;
use App\Services\DrNetwork\Flow\DrNetworkFlowFailureService;
use Illuminate\Http\Request;

class OrderConsentService
{
    public const REQUIRED_CONSENT_KEYS = [
        OrderConsent::KEY_TELEHEALTH_TERMS_CONSENT,
    ];

    public function __construct(
        private ConsentBlockingRuleEvaluator $blockingRuleEvaluator,
        private DrNetworkFlowFailureService $failureService,
    ) {}

    public function record(Order $order, array $data, Request $request): OrderConsent
    {
        $accepted = (bool) $data['accepted'];

        if ($this->isTermsConsentKey($data['consent_key'])) {
            $existingRejection = $this->termsConsentRejection($order);

            if ($existingRejection) {
                $this->applyTermsConsentRejectionHardStop($order, $existingRejection);

                return $existingRejection;
            }
        }

        $consent = OrderConsent::query()->updateOrCreate(
            [
                'order_id' => $order->id,
                'consent_key' => $data['consent_key'],
                'content_version' => $data['content_version'],
            ],
            [
                'patient_id' => $order->patient_id,
                'consultation_record_id' => $order->consultationRecord?->id,
                'consent_title' => $data['consent_title'] ?? OrderConsent::TITLE_FITBYSHOT_TERMS_CONSENT,
                'content_hash' => $data['content_hash'],
                'accepted' => $accepted,
                'accepted_at' => $accepted ? now() : null,
                'rejected_at' => $accepted ? null : now(),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'metadata' => $data['metadata'] ?? null,
            ]
        );

        if ($this->isTermsConsentKey($consent->consent_key) && ! $consent->accepted) {
            $this->applyTermsConsentRejectionHardStop($order, $consent);
        }

        return $consent;
    }

    public function termsConsentRejection(Order $order): ?OrderConsent
    {
        return OrderConsent::query()
            ->where('order_id', $order->id)
            ->where('consent_key', OrderConsent::KEY_TELEHEALTH_TERMS_CONSENT)
            ->where('accepted', false)
            ->whereNotNull('rejected_at')
            ->latest('id')
            ->first();
    }

    public function hasRejectedTermsConsent(Order $order): bool
    {
        return $this->termsConsentRejection($order) !== null;
    }

    public function missingRequiredConsentKeys(Order $order): array
    {
        if ($this->hasRejectedTermsConsent($order)) {
            return self::REQUIRED_CONSENT_KEYS;
        }

        $acceptedKeys = OrderConsent::query()
            ->where('order_id', $order->id)
            ->whereIn('consent_key', self::REQUIRED_CONSENT_KEYS)
            ->where('accepted', true)
            ->pluck('consent_key')
            ->unique()
            ->all();

        return array_values(array_diff(self::REQUIRED_CONSENT_KEYS, $acceptedKeys));
    }

    public function hasRequiredConsents(Order $order): bool
    {
        return $this->missingRequiredConsentKeys($order) === [];
    }

    private function isTermsConsentKey(string $consentKey): bool
    {
        return $consentKey === OrderConsent::KEY_TELEHEALTH_TERMS_CONSENT;
    }

    private function applyTermsConsentRejectionHardStop(Order $order, OrderConsent $consent): void
    {
        $order->loadMissing('flowRun');

        if (! $order->flowRun) {
            return;
        }

        $this->failureService->failOrder(
            $order,
            ConsentBlockingRuleEvaluator::REASON_TERMS_CONSENT_REJECTED,
            $this->blockingRuleEvaluator->termsConsentRejectionContext($consent)
        );
    }
}
