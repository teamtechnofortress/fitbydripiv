<?php

namespace App\Services\DrNetwork\IntakeQuestions;

use App\Models\ConsultationRecord;
use App\Models\Order;
use App\Models\OrderIntakeAnswer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class PreviousIntakeAnswerService
{
    public function forOrder(Order $order, array $questionKeys = []): array
    {
        Log::channel('dr_network')->info('Previous intake answer lookup started.', [
            'order_id' => $order->id,
            'order_uuid' => $order->order_uuid,
            'patient_id' => $order->patient_id,
            'product_id' => $order->product_id,
            'provided_question_key_count' => count($questionKeys),
        ]);

        if (! $order->patient_id || ! $order->product_id) {
            Log::channel('dr_network')->info('Previous intake answer lookup skipped because order is missing patient or product.', [
                'order_id' => $order->id,
                'order_uuid' => $order->order_uuid,
                'patient_id' => $order->patient_id,
                'product_id' => $order->product_id,
            ]);

            return $this->emptyResponse();
        }

        $questionKeys = collect($questionKeys)
            ->filter(fn (mixed $questionKey): bool => is_string($questionKey) && trim($questionKey) !== '')
            ->unique()
            ->values()
            ->all();

        Log::channel('dr_network')->info('Previous intake answer lookup normalized current question keys.', [
            'order_id' => $order->id,
            'order_uuid' => $order->order_uuid,
            'question_key_count' => count($questionKeys),
            'question_keys' => $questionKeys,
        ]);

        if ($questionKeys === []) {
            Log::channel('dr_network')->info('Previous intake answer lookup skipped because no current question keys were available.', [
                'order_id' => $order->id,
                'order_uuid' => $order->order_uuid,
            ]);

            return $this->emptyResponse();
        }

        Log::channel('dr_network')->info('Previous intake answer lookup fetching latest submitted consultation record.', [
            'order_id' => $order->id,
            'order_uuid' => $order->order_uuid,
            'patient_id' => $order->patient_id,
            'product_id' => $order->product_id,
        ]);

        $previousRecord = ConsultationRecord::query()
            ->whereNotNull('submitted_at')
            ->whereHas('order', function (Builder $query) use ($order, $questionKeys): void {
                $query
                    ->where('patient_id', $order->patient_id)
                    ->where('product_id', $order->product_id)
                    ->whereKeyNot($order->getKey())
                    ->where('payment_status', 'paid')
                    ->whereHas('intakeAnswers', function (Builder $query) use ($questionKeys): void {
                        $this->applyQuestionKeyFilter($query, $questionKeys);
                    });
            })
            ->with([
                'order' => function ($query) use ($questionKeys): void {
                    $query->with([
                        'intakeAnswers' => function ($query) use ($questionKeys): void {
                            $query
                                ->whereNotNull('question_key')
                                ->whereIn('question_key', $questionKeys)
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
            Log::channel('dr_network')->info('Previous intake answer lookup did not find a previous submitted consultation with matching answers.', [
                'order_id' => $order->id,
                'order_uuid' => $order->order_uuid,
                'patient_id' => $order->patient_id,
                'product_id' => $order->product_id,
            ]);

            return $this->emptyResponse();
        }

        $previousOrder = $previousRecord->order;

        Log::channel('dr_network')->info('Previous intake answer lookup found previous consultation record.', [
            'order_id' => $order->id,
            'order_uuid' => $order->order_uuid,
            'source_order_id' => $previousOrder->id,
            'source_order_uuid' => $previousOrder->order_uuid,
            'source_consultation_record_id' => $previousRecord->id,
            'source_consultation_network_case_id' => $previousRecord->network_case_id,
            'source_consultation_network_status' => $previousRecord->network_status,
            'source_consultation_internal_status' => $previousRecord->internal_status,
            'source_consultation_submitted_at' => $previousRecord->submitted_at?->toJSON(),
        ]);

        $answers = $previousOrder->intakeAnswers
            ->filter(fn (OrderIntakeAnswer $answer): bool => filled($answer->question_key))
            ->sortBy(fn (OrderIntakeAnswer $answer): string => (string) $answer->question_key)
            ->mapWithKeys(fn (OrderIntakeAnswer $answer): array => [
                $answer->question_key => [
                    'question_id' => $answer->question_id,
                    'question_key' => $answer->question_key,
                    'question_text' => $answer->question_text,
                    'input_type' => $answer->input_type,
                    'answer_value' => $answer->decodedAnswerValue(),
                    'answered_at' => $answer->updated_at?->toJSON(),
                ],
            ])
            ->all();

        if ($answers === []) {
            Log::channel('dr_network')->info('Previous intake answer lookup found a previous consultation but no mappable answers.', [
                'order_id' => $order->id,
                'order_uuid' => $order->order_uuid,
                'source_order_id' => $previousOrder->id,
                'source_order_uuid' => $previousOrder->order_uuid,
                'source_consultation_record_id' => $previousRecord->id,
            ]);

            return $this->emptyResponse();
        }

        Log::channel('dr_network')->info('Previous intake answer lookup mapped previous answers.', [
            'order_id' => $order->id,
            'order_uuid' => $order->order_uuid,
            'source_order_id' => $previousOrder->id,
            'source_order_uuid' => $previousOrder->order_uuid,
            'source_consultation_record_id' => $previousRecord->id,
            'mapped_answer_count' => count($answers),
            'mapped_question_keys' => array_keys($answers),
        ]);

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
