<?php

namespace App\Services\DrNetwork\IntakeQuestions;

use App\Models\NetworkFlowDefinition;
use App\Models\NetworkIntakeQuestion;
use App\Models\NetworkIntakeQuestionSet;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class IntakeAnswerReviewService
{
    public function __construct(
        private IntakeRuleContextBuilder $contextBuilder,
        private IntakeQuestionRuleEvaluator $ruleEvaluator,
        private IntakeAnswerBlockingRuleEvaluator $blockingRuleEvaluator,
    ) {}

    public function review(Order $order, array $submittedAnswers): array
    {
        $order->loadMissing(['flowRun', 'patient', 'product']);

        $questionSet = $this->resolveQuestionSet($order);

        if (! $questionSet) {
            throw ValidationException::withMessages([
                'question_set' => 'No published intake question set is available for this order.',
            ]);
        }

        $questions = $questionSet->questions()->get();
        $submittedByKey = $this->submittedAnswersByQuestionKey($questions, $submittedAnswers);
        $context = array_merge(
            $this->contextBuilder->build($order, $questionSet->id),
            $this->answerContext($submittedByKey)
        );
        $context = array_merge($context, $this->autoFilledAnswerContext($order, $questions, $context));

        $validations = $questions
            ->map(fn (NetworkIntakeQuestion $question): array => $this->reviewQuestion($question, $context))
            ->filter(fn (array $result): bool => $result['validations'] !== [])
            ->values()
            ->all();

        return [
            'question_set' => [
                'set_id' => $questionSet->id,
                'set_key' => $questionSet->set_key,
                'set_name' => $questionSet->set_name,
                'version' => $questionSet->version,
            ],
            'validations' => $validations,
        ];
    }

    private function resolveQuestionSet(Order $order): ?NetworkIntakeQuestionSet
    {
        $flowId = NetworkFlowDefinition::query()
            ->forNetwork((int) $order->dr_network_id)
            ->forKey((string) $order->network_flow_key)
            ->value('id');

        return NetworkIntakeQuestionSet::resolveFor(
            (int) $order->dr_network_id,
            $flowId ? (int) $flowId : null,
            $order->product?->slug,
            $order->state_code
        );
    }

    private function submittedAnswersByQuestionKey(Collection $questions, array $submittedAnswers): array
    {
        $questionsById = $questions->keyBy('id');
        $questionsByKey = $questions->keyBy('question_key');
        $answersByKey = [];

        foreach ($submittedAnswers as $index => $answer) {
            $question = null;

            if (array_key_exists('question_id', $answer) && $answer['question_id'] !== null) {
                $question = $questionsById->get((int) $answer['question_id']);
            }

            if (! $question && array_key_exists('question_key', $answer)) {
                $question = $questionsByKey->get((string) $answer['question_key']);
            }

            if (! $question) {
                throw ValidationException::withMessages([
                    "answers.{$index}.question" => 'This question is not part of the resolved intake question set for this order.',
                ]);
            }

            $answersByKey[$question->question_key] = $answer['answer_value'] ?? null;
        }

        return $answersByKey;
    }

    private function answerContext(array $answersByKey): array
    {
        $context = [];

        foreach ($answersByKey as $questionKey => $answerValue) {
            $context["answers.{$questionKey}"] = $answerValue;
        }

        return $context;
    }

    private function autoFilledAnswerContext(Order $order, Collection $questions, array $context): array
    {
        $autoFilledContext = [];

        foreach ($questions as $question) {
            if (! $question->isAutoFilled() || ! $this->ruleEvaluator->applies($question, array_merge($context, $autoFilledContext))) {
                continue;
            }

            $answerValue = $this->autoFilledAnswerValue($order, $question, array_merge($context, $autoFilledContext));

            if (! $this->isBlankAnswer($answerValue)) {
                $autoFilledContext["answers.{$question->question_key}"] = $answerValue;
            }
        }

        return $autoFilledContext;
    }

    private function reviewQuestion(NetworkIntakeQuestion $question, array $context): array
    {
        $applies = $this->ruleEvaluator->applies($question, $context);
        $answerValue = $context["answers.{$question->question_key}"] ?? null;
        $blockingRules = $applies
            ? $this->blockingRuleEvaluator->triggeredRules($question, $context)
            : [];
        $validations = [];

        if ($applies && $question->is_required && $this->isBlankAnswer($answerValue)) {
            $validations[] = [
                'type' => 'required',
                'status' => 'failed',
                'message' => 'Answer is required.',
            ];
        }

        foreach ($blockingRules as $rule) {
            $hardStopType = $rule['hard_stop_type'] ?? IntakeAnswerBlockingRuleEvaluator::HARD_STOP_REFER_OUT;

            $validations[] = [
                'type' => 'blocking_rule',
                'status' => $hardStopType === IntakeAnswerBlockingRuleEvaluator::HARD_STOP_PROVIDER_REVIEW_REQUIRED
                    ? 'provider_review_required'
                    : 'failed',
                'rule_key' => $rule['rule_key'],
                'reason' => $rule['reason'],
                'hard_stop_type' => $hardStopType,
                'message' => $rule['message'],
                'conditions' => $rule['conditions'] ?? [],
            ];
        }

        return [
            'question_id' => $question->id,
            'question_key' => $question->question_key,
            'question_text' => $question->question_text,
            'input_type' => $question->input_type,
            'answer_value' => $answerValue,
            'is_required' => $question->is_required,
            'is_applicable' => $applies,
            'validations' => $validations,
        ];
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
}
