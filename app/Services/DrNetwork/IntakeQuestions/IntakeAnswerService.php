<?php

namespace App\Services\DrNetwork\IntakeQuestions;

use App\Models\NetworkIntakeQuestion;
use App\Models\Order;
use App\Models\OrderIntakeAnswer;
use App\Services\DrNetwork\Flow\FlowRunner;

class IntakeAnswerService
{
    public function __construct(
        private FlowRunner $flowRunner,
    ) {}

    public function saveAnswer(Order $order, int $questionId, mixed $answerValue): void
    {
        $order->loadMissing('flowRun');
        $question = NetworkIntakeQuestion::query()->findOrFail($questionId);

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

        if ($order->flowRun?->current_step_key === 'intake_questions' && $this->allRequiredAnswered($order, $question->question_set_id)) {
            $this->flowRunner->advance($order->flowRun->refresh(), 'intake_questions', [
                'answers_count' => OrderIntakeAnswer::query()->where('order_id', $order->id)->count(),
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

        $answersByQuestionId = OrderIntakeAnswer::query()
            ->where('order_id', $order->id)
            ->whereIn('question_id', $questions->pluck('id'))
            ->get()
            ->mapWithKeys(fn (OrderIntakeAnswer $answer): array => [
                $answer->question_id => $answer->decodedAnswerValue(),
            ]);

        $answersByQuestionKey = $questions
            ->mapWithKeys(fn (NetworkIntakeQuestion $question): array => [
                $question->question_key => $answersByQuestionId->get($question->id),
            ])
            ->filter(fn (mixed $value): bool => ! $this->isBlankAnswer($value));

        $requiredQuestions = $questions
            ->filter(fn (NetworkIntakeQuestion $question): bool => $question->is_required)
            ->filter(fn (NetworkIntakeQuestion $question): bool => $this->questionIsApplicable($question, $answersByQuestionKey->all()));

        if ($requiredQuestions->isEmpty()) {
            return true;
        }

        return $requiredQuestions
            ->every(fn (NetworkIntakeQuestion $question): bool => ! $this->isBlankAnswer($answersByQuestionId->get($question->id)));
    }

    private function questionIsApplicable(NetworkIntakeQuestion $question, array $answersByQuestionKey): bool
    {
        if (! $question->is_conditional || empty($question->condition_rules)) {
            return true;
        }

        foreach ($question->condition_rules as $condition) {
            $dependencyKey = $condition['when'] ?? null;

            if (! $dependencyKey) {
                return false;
            }

            $answer = $answersByQuestionKey[$dependencyKey] ?? null;

            if (array_key_exists('equals', $condition) && ! $this->answerEquals($answer, $condition['equals'])) {
                return false;
            }

            if (array_key_exists('not_equals', $condition) && $this->answerEquals($answer, $condition['not_equals'])) {
                return false;
            }

            if (array_key_exists('in', $condition) && ! in_array($answer, (array) $condition['in'], true)) {
                return false;
            }
        }

        return true;
    }

    private function answerEquals(mixed $answer, mixed $expected): bool
    {
        if (is_array($answer)) {
            return in_array($expected, $answer, true);
        }

        return (string) $answer === (string) $expected;
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
