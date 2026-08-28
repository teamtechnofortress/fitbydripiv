<?php

namespace App\Services\Consent;

use App\Models\OrderConsent;

class ConsentBlockingRuleEvaluator
{
    public const RULE_TERMS_CONSENT_REJECTED = 'telehealth_terms_consent_rejected';

    public const REASON_TERMS_CONSENT_REJECTED = 'terms_consent_rejected';

    public const HARD_STOP_TERMS_CONSENT_REJECTED = 'terms_consent_rejected';

    public function termsConsentRejectionRule(): array
    {
        return $this->blockingRule(
            self::RULE_TERMS_CONSENT_REJECTED,
            self::REASON_TERMS_CONSENT_REJECTED,
            'Terms consent was rejected. This order cannot continue unless support resets or cancels it.',
            [[
                'source' => 'consents.telehealth_terms_consent.accepted',
                'operator' => 'equals',
                'value' => false,
            ]],
            self::HARD_STOP_TERMS_CONSENT_REJECTED
        );
    }

    public function termsConsentRejectionContext(OrderConsent $consent): array
    {
        $rule = $this->termsConsentRejectionRule();

        return [
            'failure_message' => $rule['message'],
            'blocking_rule_key' => $rule['rule_key'],
            'blocking_consent_id' => $consent->id,
            'blocking_consent_key' => $consent->consent_key,
            'blocking_answer' => false,
            'hard_stop_type' => $rule['hard_stop_type'],
            'conditions' => $rule['conditions'],
            'triggered_rules' => [$rule],
            'consent_content_version' => $consent->content_version,
            'consent_content_hash' => $consent->content_hash,
            'consent_rejected_at' => $consent->rejected_at,
        ];
    }

    private function blockingRule(
        string $ruleKey,
        string $reason,
        string $message,
        array $conditions,
        string $hardStopType
    ): array {
        return [
            'rule_key' => $ruleKey,
            'reason' => $reason,
            'hard_stop_type' => $hardStopType,
            'message' => $message,
            'conditions' => $conditions,
        ];
    }
}
