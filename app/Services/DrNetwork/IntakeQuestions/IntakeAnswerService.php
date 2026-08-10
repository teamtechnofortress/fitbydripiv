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

        if ($question->isAutoFilled()) {
            throw ValidationException::withMessages([
                'question_id' => 'This question is filled automatically by the system.',
            ]);
        }

        $context = $this->contextBuilder->build($order, $question->question_set_id);

        if (! $this->ruleEvaluator->applies($question, $context)) {
            throw ValidationException::withMessages([
                'question_id' => 'This question is not applicable to this patient or order.',
            ]);
        }

        OrderIntakeAnswer::query()->updateOrCreate(
            ['order_id' => $order->id, 'question_id' => $questionId],
            $this->answerAttributes($question, $answerValue)
        );

        if ($order->flowRun) {
            $order->flowRun->update([
                'context' => array_merge($order->flowRun->context ?? [], [
                    'question_set_id' => $question->question_set_id,
                ]),
            ]);
        }

        $autoFilledQuestions = $this->syncAutoFilledAnswers($order, $question->question_set_id);

        $order = $order->refresh();
        $context = $this->contextBuilder->build($order, $question->question_set_id);
        $questionsToEvaluate = collect([$question])
            ->merge($autoFilledQuestions)
            ->unique('id')
            ->values();
        $triggeredRules = $questionsToEvaluate
            ->mapWithKeys(fn (NetworkIntakeQuestion $evaluatedQuestion): array => [
                $evaluatedQuestion->id => [
                    'question' => $evaluatedQuestion,
                    'answer' => $context['answers.'.$evaluatedQuestion->question_key] ?? null,
                    'rules' => $this->blockingRuleEvaluator->triggeredRules($evaluatedQuestion, $context),
                ],
            ])
            ->filter(fn (array $entry): bool => $entry['rules'] !== [])
            ->values()
            ->all();
        $allBlockingRules = collect($triggeredRules)
            ->flatMap(fn (array $entry): array => $entry['rules'])
            ->values()
            ->all();
        $terminalBlockingEntries = array_values(array_filter(
            $triggeredRules,
            fn (array $entry): bool => collect($entry['rules'])->contains(
                fn (array $rule): bool => ($rule['hard_stop_type'] ?? IntakeAnswerBlockingRuleEvaluator::HARD_STOP_REFER_OUT) !== IntakeAnswerBlockingRuleEvaluator::HARD_STOP_PROVIDER_REVIEW_REQUIRED
            )
        ));
        $providerReviewEntries = array_values(array_filter(
            $triggeredRules,
            fn (array $entry): bool => collect($entry['rules'])->contains(
                fn (array $rule): bool => ($rule['hard_stop_type'] ?? IntakeAnswerBlockingRuleEvaluator::HARD_STOP_REFER_OUT) === IntakeAnswerBlockingRuleEvaluator::HARD_STOP_PROVIDER_REVIEW_REQUIRED
            )
        ));
        $terminalBlockingRules = $terminalBlockingEntries === []
            ? []
            : array_values(array_filter(
                $terminalBlockingEntries[0]['rules'],
                fn (array $rule): bool => ($rule['hard_stop_type'] ?? IntakeAnswerBlockingRuleEvaluator::HARD_STOP_REFER_OUT) !== IntakeAnswerBlockingRuleEvaluator::HARD_STOP_PROVIDER_REVIEW_REQUIRED
            ));
        if ($terminalBlockingRules !== []) {
            $blockingRule = $terminalBlockingRules[0];
            $blockingQuestion = $terminalBlockingEntries[0]['question'];

            $this->failureService->failOrder($order, $blockingRule['reason'], [
                'failure_message' => $blockingRule['message'],
                'blocking_rule_key' => $blockingRule['rule_key'],
                'blocking_question_id' => $blockingQuestion->id,
                'blocking_question_key' => $blockingQuestion->question_key,
                'blocking_answer' => $terminalBlockingEntries[0]['answer'],
                'hard_stop_type' => $blockingRule['hard_stop_type'] ?? IntakeAnswerBlockingRuleEvaluator::HARD_STOP_REFER_OUT,
                'conditions' => $blockingRule['conditions'],
                'triggered_rules' => $allBlockingRules,
            ]);

            return;
        }

        foreach ($providerReviewEntries as $entry) {
            $rules = array_values(array_filter(
                $entry['rules'],
                fn (array $rule): bool => ($rule['hard_stop_type'] ?? IntakeAnswerBlockingRuleEvaluator::HARD_STOP_REFER_OUT) === IntakeAnswerBlockingRuleEvaluator::HARD_STOP_PROVIDER_REVIEW_REQUIRED
            ));

            $this->syncProviderReviewRequirements($order, $entry['question'], $entry['answer'], $rules);
        }

        if ($order->flowRun?->current_step_key === 'intake_questions' && $this->allRequiredAnswered($order, $question->question_set_id)) {
            $this->flowRunner->advance($order->flowRun->refresh(), 'intake_questions', [
                'answers_count' => OrderIntakeAnswer::query()->where('order_id', $order->id)->count(),
                'provider_review_requirements' => $order->flowRun->refresh()->context['provider_review_requirements'] ?? [],
            ]);
        }
    }

    private function syncAutoFilledAnswers(Order $order, int $questionSetId): array
    {
        $order->loadMissing(['patient']);
        $filledQuestions = [];

        $questions = NetworkIntakeQuestion::query()
            ->where('question_set_id', $questionSetId)
            ->active()
            ->ordered()
            ->get();

        $context = $this->contextBuilder->build($order, $questionSetId);

        $questions
            ->filter(fn (NetworkIntakeQuestion $question): bool => $question->isAutoFilled())
            ->filter(fn (NetworkIntakeQuestion $question): bool => $this->ruleEvaluator->applies($question, $context))
            ->each(function (NetworkIntakeQuestion $question) use ($order, $context, &$filledQuestions): void {
                $answerValue = $this->autoFilledAnswerValue($order, $question, $context);

                if ($this->isBlankAnswer($answerValue)) {
                    return;
                }

                OrderIntakeAnswer::query()->updateOrCreate(
                    ['order_id' => $order->id, 'question_id' => $question->id],
                    $this->answerAttributes($question, $answerValue)
                );

                $filledQuestions[] = $question;
            });

        return $filledQuestions;
    }

    private function autoFilledAnswerValue(Order $order, NetworkIntakeQuestion $question, array $context): mixed
    {
        return match ($question->autoFillType()) {
            NetworkIntakeQuestion::AUTO_FILL_CURRENT_DATE => now()->toDateString(),
            NetworkIntakeQuestion::AUTO_FILL_PATIENT_NAME => $this->patientFullName($order),
            NetworkIntakeQuestion::AUTO_FILL_ORDER_UUID => $order->order_uuid ?? $order->uuid ?? (string) $order->id,
            NetworkIntakeQuestion::AUTO_FILL_CALCULATED_BMI => $this->calculatedBmi($context),
            NetworkIntakeQuestion::AUTO_FILL_PATIENT_HEIGHT_FEET => $this->patientHeightFeet($order),
            NetworkIntakeQuestion::AUTO_FILL_PATIENT_HEIGHT_INCHES => $this->patientHeightInches($order),
            NetworkIntakeQuestion::AUTO_FILL_PATIENT_WEIGHT => $order->patient?->weight,
            NetworkIntakeQuestion::AUTO_FILL_PATIENT_BMI => $order->patient?->bmi,
            default => null,
        };
    }

    private function calculatedBmi(array $context): ?string
    {
        $feet = $context['answers.glp1_height_feet'] ?? null;
        $inches = $context['answers.glp1_height_inches'] ?? null;
        $weight = $context['answers.glp1_weight_lbs'] ?? null;

        if (! is_numeric($feet) || ! is_numeric($inches) || ! is_numeric($weight)) {
            return null;
        }

        $totalHeightInches = ((float) $feet * 12) + (float) $inches;

        if ($totalHeightInches <= 0 || (float) $weight <= 0) {
            return null;
        }

        $bmi = ((float) $weight * 703) / ($totalHeightInches * $totalHeightInches);

        return number_format($bmi, 2, '.', '');
    }

    private function patientFullName(Order $order): ?string
    {
        $patient = $order->patient;

        if (! $patient) {
            return null;
        }

        $name = trim(implode(' ', array_filter([
            $patient->first_name ?? null,
            $patient->middle_name ?? null,
            $patient->last_name ?? null,
        ])));

        return $name === '' ? null : $name;
    }

    private function patientHeightFeet(Order $order): ?int
    {
        $height = $order->patient?->height;

        if (! is_numeric($height) || (float) $height <= 0) {
            return null;
        }

        return intdiv((int) round((float) $height), 12);
    }

    private function patientHeightInches(Order $order): ?int
    {
        $height = $order->patient?->height;

        if (! is_numeric($height) || (float) $height <= 0) {
            return null;
        }

        return (int) round((float) $height) % 12;
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
                'hard_stop_type' => IntakeAnswerBlockingRuleEvaluator::HARD_STOP_PROVIDER_REVIEW_REQUIRED,
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

    private function answerAttributes(NetworkIntakeQuestion $question, mixed $answerValue): array
    {
        return [
            'question_key' => $question->question_key,
            'question_text' => $question->question_text,
            'input_type' => $question->input_type,
            'network_field_mapping' => $question->network_field_mapping,
            'answer_value' => $this->encodeAnswerValue($answerValue),
        ];
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
