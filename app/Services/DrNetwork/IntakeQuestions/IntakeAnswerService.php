<?php

namespace App\Services\DrNetwork\IntakeQuestions;

use App\Models\NetworkIntakeQuestion;
use App\Models\Order;
use App\Models\OrderIntakeAnswer;
use App\Services\DrNetwork\Flow\DrNetworkFlowFailureService;
use App\Services\DrNetwork\Flow\FlowRunner;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class IntakeAnswerService
{
    public function __construct(
        private FlowRunner $flowRunner,
        private IntakeRuleContextBuilder $contextBuilder,
        private IntakeQuestionRuleEvaluator $ruleEvaluator,
        private IntakeAnswerBlockingRuleEvaluator $blockingRuleEvaluator,
        private DrNetworkFlowFailureService $failureService,
    ) {}

    public function saveAnswer(Order $order, int $questionId, mixed $answerValue): void
    {
        $order->loadMissing(['flowRun', 'patient', 'product']);
        $question = NetworkIntakeQuestion::query()
            ->active()
            ->findOrFail($questionId);

        $context = $this->contextBuilder->build($order, $question->question_set_id);

        if (! $this->ruleEvaluator->applies($question, $context)) {
            throw ValidationException::withMessages([
                'question_id' => 'This question is not applicable to this patient or order.',
            ]);
        }

        OrderIntakeAnswer::query()->updateOrCreate(
            ['order_id' => $order->id, 'question_id' => $questionId],
            ['answer_value' => $this->encodeAnswerValue($answerValue)]
        );

        if ($order->flowRun) {
            $order->flowRun->update([
                'context' => array_merge($order->flowRun->context ?? [], [
                    'question_set_id' => $question->question_set_id,
                ]),
            ]);
        }

        $order = $order->refresh();
        $context = $this->contextBuilder->build($order, $question->question_set_id);
        $blockingRules = $this->blockingRuleEvaluator->triggeredRules($question, $context);
        $terminalBlockingRules = array_values(array_filter(
            $blockingRules,
            fn (array $rule): bool => ($rule['hard_stop_type'] ?? 'refer_out') !== 'provider_review_required'
        ));
        $providerReviewRules = array_values(array_filter(
            $blockingRules,
            fn (array $rule): bool => ($rule['hard_stop_type'] ?? 'refer_out') === 'provider_review_required'
        ));

        if ($terminalBlockingRules !== []) {
            $blockingRule = $terminalBlockingRules[0];

            $this->failureService->failOrder($order, $blockingRule['reason'], [
                'failure_message' => $blockingRule['message'],
                'blocking_rule_key' => $blockingRule['rule_key'],
                'blocking_question_id' => $question->id,
                'blocking_question_key' => $question->question_key,
                'blocking_answer' => $answerValue,
                'hard_stop_type' => $blockingRule['hard_stop_type'] ?? 'refer_out',
                'conditions' => $blockingRule['conditions'],
                'triggered_rules' => $blockingRules,
            ]);

            return;
        }

        $this->syncProviderReviewRequirements($order, $question, $answerValue, $providerReviewRules);

        if ($order->flowRun?->current_step_key === 'intake_questions' && $this->allRequiredAnswered($order, $question->question_set_id)) {
            $this->flowRunner->advance($order->flowRun->refresh(), 'intake_questions', [
                'answers_count' => OrderIntakeAnswer::query()->where('order_id', $order->id)->count(),
                'provider_review_requirements' => $order->flowRun->refresh()->context['provider_review_requirements'] ?? [],
            ]);
        }
    }

    private function syncProviderReviewRequirements(
        Order $order,
        NetworkIntakeQuestion $question,
        mixed $answerValue,
        array $rules
    ): void {
        $flowRun = $order->flowRun;

        if (! $flowRun) {
            return;
        }

        $context = $flowRun->context ?? [];
        $existingRequirements = is_array($context['provider_review_requirements'] ?? null)
            ? $context['provider_review_requirements']
            : [];

        $requirements = array_values(array_filter(
            $existingRequirements,
            fn (array $requirement): bool => (int) ($requirement['question_id'] ?? 0) !== (int) $question->id
        ));

        foreach ($rules as $rule) {
            $requirements[] = [
                'rule_key' => $rule['rule_key'],
                'reason' => $rule['reason'],
                'message' => $rule['message'],
                'hard_stop_type' => 'provider_review_required',
                'substance' => $rule['substance'] ?? null,
                'question_id' => $question->id,
                'question_key' => $question->question_key,
                'question_text' => $question->question_text,
                'answer' => $answerValue,
                'conditions' => $rule['conditions'] ?? [],
                'status' => 'open',
                'recorded_at' => now()->toIso8601String(),
            ];
        }

        $context['provider_review_requirements'] = $requirements;
        $context['has_provider_review_requirements'] = $requirements !== [];
        $context['provider_review_required_substances'] = collect($requirements)
            ->pluck('substance')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $flowRun->update(['context' => $context]);

        if ($rules !== []) {
            Log::channel('dr_network')->info('Provider review requirement recorded from intake answer.', [
                'order_id' => $order->id,
                'flow_run_id' => $flowRun->id,
                'question_id' => $question->id,
                'question_key' => $question->question_key,
                'rule_keys' => collect($rules)->pluck('rule_key')->values()->all(),
                'substances' => $context['provider_review_required_substances'],
            ]);
        }
    }

    private function allRequiredAnswered(Order $order, int $questionSetId): bool
    {
        $questions = NetworkIntakeQuestion::query()
            ->where('question_set_id', $questionSetId)
            ->active()
            ->ordered()
            ->get();

        $context = $this->contextBuilder->build($order, $questionSetId);
        $applicableQuestions = $questions
            ->filter(fn (NetworkIntakeQuestion $question): bool => $this->ruleEvaluator->applies($question, $context));

        $answersByQuestionId = OrderIntakeAnswer::query()
            ->where('order_id', $order->id)
            ->whereIn('question_id', $applicableQuestions->pluck('id'))
            ->get()
            ->mapWithKeys(fn (OrderIntakeAnswer $answer): array => [
                $answer->question_id => $answer->decodedAnswerValue(),
            ]);

        $requiredQuestions = $applicableQuestions
            ->filter(fn (NetworkIntakeQuestion $question): bool => $question->is_required)
            ->values();

        if ($requiredQuestions->isEmpty()) {
            return true;
        }

        return $requiredQuestions
            ->every(fn (NetworkIntakeQuestion $question): bool => ! $this->isBlankAnswer($answersByQuestionId->get($question->id)));
    }

    private function isBlankAnswer(mixed $answer): bool
    {
        if ($answer === null) {
            return true;
        }

        if (is_array($answer)) {
            return $answer === [];
        }

        return trim((string) $answer) === '';
    }

    private function encodeAnswerValue(mixed $answerValue): ?string
    {
        if ($answerValue === null) {
            return null;
        }

        return is_array($answerValue)
            ? json_encode($answerValue, JSON_THROW_ON_ERROR)
            : (string) $answerValue;
    }
}
