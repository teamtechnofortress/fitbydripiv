<?php

namespace App\Services\DrNetwork\IntakeQuestions;

use App\Models\NetworkIntakeQuestion;

class IntakeAnswerBlockingRuleEvaluator
{
    public const HARD_STOP_REFER_OUT = 'refer_out';

    public const HARD_STOP_PROVIDER_REVIEW_REQUIRED = 'provider_review_required';

    public const HARD_STOP_TYPES = [
        self::HARD_STOP_REFER_OUT,
        self::HARD_STOP_PROVIDER_REVIEW_REQUIRED,
    ];

    public function __construct(
        private IntakeQuestionRuleEvaluator $ruleEvaluator,
    ) {}

    public function firstTriggeredRule(NetworkIntakeQuestion $question, array $context): ?array
    {
        return $this->triggeredRules($question, $context)[0] ?? null;
    }

    public function triggeredRules(NetworkIntakeQuestion $question, array $context): array
    {
        $rules = $question->network_validation['blocking_rules'] ?? [];

        if (! is_array($rules) || $rules === []) {
            return [];
        }

        $triggeredRules = [];

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            if ($this->ruleEvaluator->conditionsPass($rule['conditions'] ?? [], $context)) {
                $triggeredRules[] = [
                    'rule_key' => $rule['rule_key'] ?? $question->question_key.'_blocking_rule',
                    'reason' => $rule['reason'] ?? 'intake_answer_ineligible',
                    'message' => $rule['message'] ?? 'You are not eligible for this consultation.',
                    'hard_stop_type' => $rule['hard_stop_type'] ?? self::HARD_STOP_REFER_OUT,
                    'substance' => $rule['substance'] ?? $question->metadata['substance'] ?? null,
                    'conditions' => $rule['conditions'] ?? [],
                ];
            }
        }

        return $triggeredRules;
    }
}
