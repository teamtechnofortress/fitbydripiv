<?php

namespace App\Services\DrNetwork\IntakeQuestions;

use App\Models\NetworkIntakeQuestion;

class IntakeAnswerBlockingRuleEvaluator
{
    public function __construct(
        private IntakeQuestionRuleEvaluator $ruleEvaluator,
    ) {}

    public function firstTriggeredRule(NetworkIntakeQuestion $question, array $context): ?array
    {
        $rules = $question->network_validation['blocking_rules'] ?? [];

        if (! is_array($rules) || $rules === []) {
            return null;
        }

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            if ($this->ruleEvaluator->conditionsPass($rule['conditions'] ?? [], $context)) {
                return [
                    'rule_key' => $rule['rule_key'] ?? $question->question_key.'_blocking_rule',
                    'reason' => $rule['reason'] ?? 'intake_answer_ineligible',
                    'message' => $rule['message'] ?? 'You are not eligible for this consultation.',
                    'conditions' => $rule['conditions'] ?? [],
                ];
            }
        }

        return null;
    }
}
