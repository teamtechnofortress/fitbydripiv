<?php

namespace App\Services\DrNetwork\IntakeQuestions;

use App\Models\NetworkIntakeQuestion;
use App\Models\Order;
use App\Models\OrderIntakeAnswer;
use App\Services\DrNetwork\Flow\DrNetworkFlowFailureService;
use App\Services\DrNetwork\Flow\FlowRunner;
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
        $blockingRule = $this->blockingRuleEvaluator->firstTriggeredRule($question, $context);

        if ($blockingRule !== null) {
            $this->failureService->failOrder($order, $blockingRule['reason'], [
                'failure_message' => $blockingRule['message'],
                'blocking_rule_key' => $blockingRule['rule_key'],
                'blocking_question_id' => $question->id,
                'blocking_question_key' => $question->question_key,
                'blocking_answer' => $answerValue,
                'conditions' => $blockingRule['conditions'],
            ]);

            return;
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
