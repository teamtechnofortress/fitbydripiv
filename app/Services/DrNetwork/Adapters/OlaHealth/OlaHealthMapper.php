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
            'user_data' => $this->mapUserData($order),
            'address' => $this->mapAddress($order),
            'service_data' => [
                'question_answer' => $this->mapAnswers($order),
            ],
            'identifier' => $this->mapIdentifier($order, $context),
            'transaction_id' => (string) ($order->order_uuid ?? $order->id),
            'pharmacyDetails' => $this->mapPharmacyDetails($order),
            'schedule' => $this->mapSchedule($context),
            'user_insurance' => [],
            'files' => $this->mapFiles($order),
        ];
    }

    private function mapUserData(Order $order): array
    {
        $patient = $order->patient;

        return [
            'first_name' => $patient?->first_name ?? '',
            'last_name' => $patient?->last_name ?? '',
            'gender' => $patient?->gender ?? '',
            'dob' => $patient?->birthday ? (string) $patient->birthday : '',
            'email' => $patient?->email ?? '',
            'phone' => $patient?->cell ?? $patient?->phone ?? '',
            'role' => 'USER',
            'sub_role' => '',
            'release_medical' => false,
        ];
    }

    private function mapAddress(Order $order): array
    {
        $patient = $order->patient;
        $street = (string) ($patient?->address ?? '');
        $city = (string) ($patient?->city ?? '');
        $state = (string) ($order->state_code ?: $patient?->state ?: '');
        $zip = (string) ($patient?->zip ?? '');

        return [[
            'use' => 'home',
            'text' => trim("{$street} {$city} {$state} {$zip}"),
            'street1' => $street,
            'city' => $city,
            'state' => $state,
            'postalCode' => $zip,
            'line' => array_values(array_filter([$street, $city, $state, $zip])),
            'type' => 'both',
        ]];
    }

    private function mapIdentifier(Order $order, array $context): array
    {
        $externalConfig = is_array($context['external_config'] ?? null)
            ? $context['external_config']
            : [];

        return [
            'service' => $context['service_key']
                ?? $context['external_service_key']
                ?? $context['external_service_id']
                ?? $context['network_product_identifier']
                ?? $order->network_product_identifier,
            'sessionType' => $context['session_type'] ?? $externalConfig['session_type'] ?? null,
            'scheduleType' => $this->scheduleType($order),
        ];
    }

    private function mapPharmacyDetails(Order $order): array
    {
        return [
            'pharmacy_name' => '',
            'pharmacy_address' => '',
            'pharmacy_phone' => '',
            'pharmacy_fax' => '',
            'pharmacy_ncpdp_id' => '',
        ];
    }

    private function mapAnswers(Order $order): array
    {
        return OrderIntakeAnswer::query()
            ->where('order_id', $order->id)
            ->with('question')
            ->get()
            ->map(fn (OrderIntakeAnswer $answer): array => [
                'question_text' => $answer->question?->network_field_mapping
                    ?: $answer->question?->question_text
                    ?: $answer->question?->question_key
                    ?: "Question {$answer->question_id}",
                'answer' => $this->stringAnswer($answer->decodedAnswerValue()),
                'other_text' => '',
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
            ->filter(fn (OrderDocument $document): bool => Storage::exists($document->file_path))
            ->map(fn (OrderDocument $document): array => [
                'path' => $document->file_path,
                'content_type' => $document->mime_type,
                'file_name' => $document->original_filename,
            ])
            ->values()
            ->all();
    }

    private function mapSchedule(array $context): ?array
    {
        if (empty($context['slot_id'])) {
            return null;
        }

        return [
            'schedule_start_date' => $context['slot_scheduled_at'] ?? $context['scheduled_time'] ?? null,
            'schedule_end_date' => $context['slot_schedule_end_at'] ?? null,
            'provider_guid' => $context['provider_id'] ?? $context['slot_id'],
        ];
    }

    private function scheduleType(Order $order): string
    {
        return match ($order->purchase_type) {
            'subscription' => 'subscription',
            default => 'one-time',
        };
    }

    private function stringAnswer(mixed $answer): string
    {
        if (is_array($answer)) {
            return implode(', ', array_map(fn (mixed $value): string => (string) $value, $answer));
        }

        if (is_bool($answer)) {
            return $answer ? 'Yes' : 'No';
        }

        return (string) $answer;
    }
}
