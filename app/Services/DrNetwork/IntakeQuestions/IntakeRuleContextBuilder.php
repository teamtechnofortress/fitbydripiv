<?php

namespace App\Services\DrNetwork\IntakeQuestions;

use App\Models\Order;
use App\Models\OrderIntakeAnswer;
use Carbon\Carbon;
use Throwable;

class IntakeRuleContextBuilder
{
    public function build(Order $order, ?int $questionSetId = null): array
    {
        $order->loadMissing(['patient', 'product']);
        $patient = $order->patient;

        return array_merge([
            'patient.gender' => $this->normalizeGender($patient?->gender),
            'patient.age' => $this->patientAge($patient?->age, $patient?->birthday),
            'order.state_code' => $order->state_code ? strtoupper($order->state_code) : null,
            'order.product_id' => $order->product_id,
            'order.product_slug' => $order->product?->slug,
            'flow.key' => $order->network_flow_key,
        ], $this->answerContext($order, $questionSetId));
    }

    private function answerContext(Order $order, ?int $questionSetId): array
    {
        $answers = OrderIntakeAnswer::query()
            ->with('question')
            ->where('order_id', $order->id)
            ->when($questionSetId !== null, function ($query) use ($questionSetId): void {
                $query->whereHas('question', function ($questionQuery) use ($questionSetId): void {
                    $questionQuery->where('question_set_id', $questionSetId);
                });
            })
            ->get();

        return $answers
            ->filter(fn (OrderIntakeAnswer $answer): bool => filled($answer->resolvedQuestionKey()))
            ->mapWithKeys(fn (OrderIntakeAnswer $answer): array => [
                'answers.'.$answer->resolvedQuestionKey() => $answer->decodedAnswerValue(),
            ])
            ->all();
    }

    private function normalizeGender(?string $gender): ?string
    {
        $gender = strtolower(trim((string) $gender));

        return match ($gender) {
            'm', 'male' => 'male',
            'f', 'female' => 'female',
            default => $gender === '' ? null : $gender,
        };
    }

    private function patientAge(mixed $age, mixed $birthday): ?int
    {
        if (is_numeric($age)) {
            return (int) $age;
        }

        if (! $birthday) {
            return null;
        }

        try {
            return Carbon::parse($birthday)->age;
        } catch (Throwable) {
            return null;
        }
    }
}
