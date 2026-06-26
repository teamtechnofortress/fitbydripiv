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
        $requiredQuestions = NetworkIntakeQuestion::query()
            ->where('question_set_id', $questionSetId)
            ->required()
            ->active()
            ->pluck('id');

        if ($requiredQuestions->isEmpty()) {
            return true;
        }

        $answeredIds = OrderIntakeAnswer::query()
            ->where('order_id', $order->id)
            ->whereIn('question_id', $requiredQuestions)
            ->pluck('question_id');

        return $requiredQuestions->diff($answeredIds)->isEmpty();
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
