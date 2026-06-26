<?php

namespace App\Services\DrNetwork\Adapters\OlaHealth;

use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderIntakeAnswer;
use Illuminate\Support\Facades\Storage;

class OlaHealthMapper
{
    public function buildSubmissionPayload(Order $order, array $context): array
    {
        $order->loadMissing(['patient', 'product']);

        return [
            'patient' => $this->mapPatient($order),
            'service' => $this->mapService($order, $context),
            'answers' => $this->mapAnswers($order),
            'files' => $this->mapFiles($order),
            'schedule' => $this->mapSchedule($context),
        ];
    }

    private function mapPatient(Order $order): array
    {
        $patient = $order->patient;

        return [
            'first_name' => $patient?->first_name ?? '',
            'last_name' => $patient?->last_name ?? '',
            'email' => $patient?->email ?? '',
            'phone' => $patient?->cell ?? $patient?->phone ?? '',
            'date_of_birth' => $patient?->birthday ? (string) $patient->birthday : '',
            'gender' => $patient?->gender ?? '',
            'address' => [
                'line1' => $patient?->address ?? '',
                'line2' => '',
                'city' => $patient?->city ?? '',
                'state' => $patient?->state ?? $order->state_code ?? '',
                'zipcode' => $patient?->zip ?? '',
                'country' => 'US',
            ],
        ];
    }

    private function mapService(Order $order, array $context): array
    {
        return [
            'identifier' => [
                'service' => $context['network_product_identifier'] ?? $order->network_product_identifier,
                'sheet_code' => null,
            ],
            'order_reference' => (string) $order->id,
            'product_name' => $order->product?->name,
        ];
    }

    private function mapAnswers(Order $order): array
    {
        return OrderIntakeAnswer::query()
            ->where('order_id', $order->id)
            ->with('question')
            ->get()
            ->map(fn (OrderIntakeAnswer $answer): array => [
                'question_key' => $answer->question?->network_field_mapping
                    ?: $answer->question?->question_key
                    ?: (string) $answer->question_id,
                'answer' => $answer->decodedAnswerValue(),
            ])
            ->values()
            ->all();
    }

    private function mapFiles(Order $order): array
    {
        return OrderDocument::query()
            ->where('order_id', $order->id)
            ->where('status', OrderDocument::STATUS_VERIFIED)
            ->with('documentType')
            ->get()
            ->map(function (OrderDocument $document): array {
                $fileContents = Storage::exists($document->file_path)
                    ? Storage::get($document->file_path)
                    : '';

                return [
                    'type' => $document->documentType?->key,
                    'content' => base64_encode($fileContents),
                    'content_type' => $document->mime_type,
                    'file_name' => $document->original_filename,
                ];
            })
            ->values()
            ->all();
    }

    private function mapSchedule(array $context): ?array
    {
        if (empty($context['slot_id'])) {
            return null;
        }

        return [
            'schedule_id' => $context['slot_schedule_id'] ?? null,
            'slot_id' => $context['slot_id'],
            'provider_id' => $context['provider_id'] ?? null,
            'scheduled_time' => $context['slot_scheduled_at'] ?? $context['scheduled_time'] ?? null,
        ];
    }
}
