<?php

namespace App\Services\DrNetwork\IntakeQuestions;

use App\Models\ConsultationRecord;
use App\Models\Order;
use App\Models\OrderIntakeAnswer;
use Illuminate\Database\Eloquent\Builder;

class PreviousIntakeAnswerService
{
    public function forOrder(Order $order, array $questionKeys = []): array
    {
        if (! $order->patient_id || ! $order->product_id) {
            return $this->emptyResponse();
        }

        $questionKeys = collect($questionKeys)
            ->filter(fn (mixed $questionKey): bool => is_string($questionKey) && trim($questionKey) !== '')
            ->unique()
            ->values()
            ->all();

        if ($questionKeys === []) {
            return $this->emptyResponse();
        }

        $previousRecord = ConsultationRecord::query()
            ->whereNotNull('submitted_at')
            ->whereHas('order', function (Builder $query) use ($order, $questionKeys): void {
                $query
                    ->where('patient_id', $order->patient_id)
                    ->where('product_id', $order->product_id)
                    ->whereKeyNot($order->getKey())
                    ->where('payment_status', 'paid')
                    ->whereHas('intakeAnswers.question', function (Builder $query) use ($questionKeys): void {
                        $this->applyQuestionKeyFilter($query, $questionKeys);
                    });
            })
            ->with([
                'order' => function ($query) use ($questionKeys): void {
                    $query->with([
                        'intakeAnswers' => function ($query) use ($questionKeys): void {
                            $query
                                ->whereHas('question', function (Builder $query) use ($questionKeys): void {
                                    $this->applyQuestionKeyFilter($query, $questionKeys);
                                })
                                ->with([
                                    'question:id,question_set_id,question_key,question_text,input_type,sort_order',
                                ])
                                ->latest('updated_at')
                                ->latest('id');
                        },
                    ]);
                },
            ])
            ->latest('submitted_at')
            ->latest('id')
            ->first();

        if (! $previousRecord?->order) {
            return $this->emptyResponse();
        }

        $previousOrder = $previousRecord->order;

        $answers = $previousOrder->intakeAnswers
            ->filter(fn (OrderIntakeAnswer $answer): bool => $answer->question !== null && filled($answer->question->question_key))
            ->sortBy(fn (OrderIntakeAnswer $answer): string => sprintf(
                '%010d:%010d',
                $answer->question->sort_order,
                $answer->question->id
            ))
            ->mapWithKeys(fn (OrderIntakeAnswer $answer): array => [
                $answer->question->question_key => [
                    'question_id' => $answer->question_id,
                    'question_key' => $answer->question->question_key,
                    'question_text' => $answer->question->question_text,
                    'input_type' => $answer->question->input_type,
                    'answer_value' => $answer->decodedAnswerValue(),
                    'answered_at' => $answer->updated_at?->toJSON(),
                ],
            ])
            ->all();

        if ($answers === []) {
            return $this->emptyResponse();
        }

        return [
            'exists' => true,
            'source_order_id' => $previousOrder->id,
            'source_order_uuid' => $previousOrder->order_uuid,
            'source_order_payment_status' => $previousOrder->payment_status,
            'source_order_created_at' => $previousOrder->created_at?->toJSON(),
            'source_consultation_record_id' => $previousRecord->id,
            'source_consultation_network_case_id' => $previousRecord->network_case_id,
            'source_consultation_network_status' => $previousRecord->network_status,
            'source_consultation_internal_status' => $previousRecord->internal_status,
            'source_consultation_submitted_at' => $previousRecord->submitted_at?->toJSON(),
            'source_consultation_resolved_at' => $previousRecord->resolved_at?->toJSON(),
            'answers_by_question_key' => $answers,
        ];
    }

    private function applyQuestionKeyFilter(Builder $query, array $questionKeys): void
    {
        $query->whereIn('question_key', $questionKeys);
    }

    private function emptyResponse(): array
    {
        return [
            'exists' => false,
            'source_order_id' => null,
            'source_order_uuid' => null,
            'source_order_payment_status' => null,
            'source_order_created_at' => null,
            'source_consultation_record_id' => null,
            'source_consultation_network_case_id' => null,
            'source_consultation_network_status' => null,
            'source_consultation_internal_status' => null,
            'source_consultation_submitted_at' => null,
            'source_consultation_resolved_at' => null,
            'answers_by_question_key' => [],
        ];
    }
}
