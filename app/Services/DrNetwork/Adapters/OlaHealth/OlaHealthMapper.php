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
                'question_answer' => $this->mapAnswers($order, $context),
            ],
            'identifier' => $this->mapIdentifier($order, $context),
            'transaction_id' => (string) ($order->order_uuid ?? $order->id),
            'pharmacyDetails' => $this->mapPharmacyDetails($order),
            'schedule' => $this->mapSchedule($context),
            'schedule_required' => $this->scheduleRequired($order, $context),
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

    private function mapAnswers(Order $order, array $context): array
    {
        $answers = OrderIntakeAnswer::query()
            ->where('order_id', $order->id)
            ->with('question')
            ->get()
            ->map(fn (OrderIntakeAnswer $answer): array => [
                'question_text' => $answer->resolvedNetworkFieldMapping()
                    ?: $answer->resolvedQuestionText()
                    ?: $answer->resolvedQuestionKey()
                    ?: "Question {$answer->question_id}",
                'answer' => $this->stringAnswer($this->displayAnswer($answer)),
                'other_text' => '',
            ])
            ->values()
            ->all();

        return array_merge($answers, $this->mapProviderReviewRequirements($context));
    }

    private function displayAnswer(OrderIntakeAnswer $answer): mixed
    {
        $value = $answer->decodedAnswerValue();
        $optionLabels = $this->optionLabels($answer);

        if ($optionLabels === []) {
            return $value;
        }

        if (is_array($value)) {
            return array_map(
                fn (mixed $selectedValue): string => $optionLabels[(string) $selectedValue] ?? (string) $selectedValue,
                $value
            );
        }

        return $optionLabels[(string) $value] ?? $value;
    }

    private function optionLabels(OrderIntakeAnswer $answer): array
    {
        $options = $answer->question?->options;

        if (! is_array($options)) {
            return [];
        }

        return collect($options)
            ->mapWithKeys(function (array $option): array {
                $value = $option['value'] ?? $option['id'] ?? null;
                $label = $option['label'] ?? null;

                if ($value === null || $label === null) {
                    return [];
                }

                return [(string) $value => (string) $label];
            })
            ->all();
    }

    private function mapProviderReviewRequirements(array $context): array
    {
        $requirements = $context['provider_review_requirements'] ?? [];

        if (! is_array($requirements) || $requirements === []) {
            return [];
        }

        return collect($requirements)
            ->map(function (array $requirement): array {
                $substance = $requirement['substance'] ?? 'selected treatment';
                $message = $requirement['message'] ?? 'Provider review is required before approval.';
                $questionKey = $requirement['question_key'] ?? 'unknown_question';
                $answer = $this->stringAnswer($requirement['answer'] ?? '');

                return [
                    'question_text' => sprintf('Provider review required for %s', $substance),
                    'answer' => sprintf('%s Triggered by %s. Patient answer: %s', $message, $questionKey, $answer),
                    'other_text' => '',
                ];
            })
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

    private function scheduleRequired(Order $order, array $context): bool
    {
        $externalConfig = is_array($context['external_config'] ?? null)
            ? $context['external_config']
            : [];

        if (array_key_exists('schedule_required', $externalConfig)) {
            return (bool) $externalConfig['schedule_required'];
        }

        $flowKey = strtolower((string) ($order->network_flow_key ?? ''));

        return str_contains($flowKey, 'video_consultation');
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
