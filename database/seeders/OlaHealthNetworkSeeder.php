<?php

namespace Database\Seeders;

use App\Models\DrNetwork;
use App\Models\DrNetworkConfigValue;
use App\Models\NetworkFlowDefinition;
use App\Models\NetworkStateMapping;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OlaHealthNetworkSeeder extends Seeder
{
    public const ASYNC_FLOW_KEY = 'ola_health_async_review';

    public const VIDEO_FLOW_KEY = 'ola_health_video_consultation';

    // public const FOLLOW_UP_ASYNC_FLOW_KEY = 'ola_health_follow_up_async_review';

    private const VIDEO_STATE_CODES = [
        'AR',
        'KS',
        'MO',
        'MS',
        'ND',
        'NM',
        'OR',
        'WV',
        'DC',
    ];

    public function run(): void
    {
        $existingOlaHealth = DrNetwork::query()
            ->where('slug', 'ola-health')
            ->with('configValues')
            ->first();
        $webhookEndpointTokenHash = $this->webhookEndpointTokenHash($existingOlaHealth);

        $olaHealth = DrNetwork::query()->updateOrCreate(
            ['slug' => 'ola-health'],
            [
                'name' => 'Ola Health',
                'adapter_key' => 'ola_health',
                'integration_mode' => DrNetwork::INTEGRATION_MODE_API,
                'status' => DrNetwork::STATUS_ACTIVE,
                'is_default' => false,
                'settings' => [
                    'api_base_url' => env('OLA_HEALTH_API_URL', 'https://dev-api.ola-digital-int.com'),
                    'token_expiry_minutes' => 30,
                    'retry_attempts' => 3,
                    'retry_delay_seconds' => 5,
                    'webhook_endpoint_token_hash' => $webhookEndpointTokenHash,
                    'webhook_signatures_enabled' => false,
                ],
                'metadata' => [
                    'supports_video' => true,
                    'supports_async' => true,
                    'requires_provider_scheduling' => true,
                    'document_verification' => 'liveness_required_for_video',
                    'journey_steps' => $this->journeySteps(),
                ],
                'feature_flags' => [
                    'video_consultation' => true,
                    'async_review' => true,
                    // 'follow_up_async_review' => true,
                    'checkout_step' => true,
                    'payment_confirmation_step' => true,
                    'prescription_delivery' => false,
                    'webhook_enabled' => true,
                    'polling_enabled' => true,
                ],
                'config_version' => 1,
            ]
        );

        $this->seedConfigValues($olaHealth);

        $asyncFlow = $this->updateOrCreateFlow(
            network: $olaHealth,
            legacyFlowKey: 'async_review',
            flowKey: self::ASYNC_FLOW_KEY,
            values: [
                'name' => 'Ola Health Async Consultation Review',
                'description' => 'Patient submits intake forms, then Ola Health reviews and decides asynchronously.',
                'network_fee_amount' => 15,
                'patient_fee_amount' => 25,
                'steps' => [
                    [
                        'step_key' => 'document_upload',
                        'name' => 'Upload Documents',
                        'description' => 'Upload government-issued ID.',
                        'required' => true,
                        'order' => 1,
                    ],
                    [
                        'step_key' => 'intake_questions',
                        'name' => 'Answer Medical Questions',
                        'description' => 'Provide medical history, allergies, medications, and symptoms.',
                        'required' => true,
                        'order' => 2,
                    ],
                    ...$this->paymentJourneySteps(3),
                    [
                        'step_key' => 'review_and_submit',
                        'name' => 'Review and Submit',
                        'description' => 'Review information and submit to provider.',
                        'required' => true,
                        'order' => 5,
                    ],
                    [
                        'step_key' => 'provider_review',
                        'name' => 'Ola Health Provider Review',
                        'description' => 'Wait for Ola Health provider review and decision.',
                        'required' => true,
                        'order' => 6,
                    ],
                ],
                'is_active' => true,
            ]
        );

        $videoFlow = $this->updateOrCreateFlow(
            network: $olaHealth,
            legacyFlowKey: 'video_consultation',
            flowKey: self::VIDEO_FLOW_KEY,
            values: [
                'name' => 'Ola Health Video Consultation',
                'description' => 'Patient submits intake, selects an Ola Health provider slot, then attends a video consultation.',
                'network_fee_amount' => 20,
                'patient_fee_amount' => 30,
                'steps' => [
                    [
                        'step_key' => 'document_upload',
                        'name' => 'Upload Documents',
                        'description' => 'Upload government-issued ID.',
                        'required' => true,
                        'order' => 1,
                    ],
                    [
                        'step_key' => 'intake_questions',
                        'name' => 'Answer Medical Questions',
                        'description' => 'Provide medical history, allergies, medications, symptoms, and insurance information.',
                        'required' => true,
                        'order' => 2,
                    ],
                    ...$this->paymentJourneySteps(3),
                    [
                        'step_key' => 'slot_selection',
                        'name' => 'Select Appointmenchect Time',
                        'description' => 'Choose an available provider time for video consultation.',
                        'required' => true,
                        'order' => 5,
                    ],
                    [
                        'step_key' => 'review_and_submit',
                        'name' => 'Confirm Appointment',
                        'description' => 'Confirm appointment details and submit.',
                        'required' => true,
                        'order' => 6,
                    ],
                    [
                        'step_key' => 'provider_review',
                        'name' => 'Ola Health Provider Review',
                        'description' => 'Wait for Ola Health provider review and decision.',
                        'required' => true,
                        'order' => 6,
                    ],
                ],
                'is_active' => true,
            ]
        );

        // Follow-up async flow is temporarily disabled.
        // $this->updateOrCreateFlow(
        //     network: $olaHealth,
        //     legacyFlowKey: 'follow_up_async_review',
        //     flowKey: self::FOLLOW_UP_ASYNC_FLOW_KEY,
        //     values: [
        //         'name' => 'Ola Health Follow-up Async Consultation Review',
        //         'description' => 'Patient submits follow-up information, then Ola Health reviews and decides asynchronously.',
        //         'steps' => [
        //             [
        //                 'step_key' => 'intake_questions',
        //                 'name' => 'Answer Follow-up Questions',
        //                 'description' => 'Provide current symptoms, progress, medication response, and changes since the prior consultation.',
        //                 'required' => true,
        //                 'order' => 1,
        //             ],
        //             [
        //                 'step_key' => 'review_and_submit',
        //                 'name' => 'Review and Submit',
        //                 'description' => 'Review follow-up information and submit to provider.',
        //                 'required' => true,
        //                 'order' => 2,
        //             ],
        //             [
        //                 'step_key' => 'provider_review',
        //                 'name' => 'Ola Health Provider Review',
        //                 'description' => 'Wait for Ola Health provider review and decision.',
        //                 'required' => true,
        //                 'order' => 3,
        //             ],
        //         ],
        //         'is_active' => true,
        //     ]
        // );

        $states = State::query()->active()->get();

        foreach ($states as $state) {
            $flow = in_array($state->state_code, self::VIDEO_STATE_CODES, true)
                ? $videoFlow
                : $asyncFlow;

            NetworkStateMapping::query()
                ->where('state_id', $state->id)
                ->where('dr_network_id', $olaHealth->id)
                ->where('flow_id', '!=', $flow->id)
                ->update(['is_active' => false]);

            NetworkStateMapping::query()->updateOrCreate(
                [
                    'state_id' => $state->id,
                    'dr_network_id' => $olaHealth->id,
                    'flow_id' => $flow->id,
                ],
                [
                    'priority' => 1,
                    'is_active' => true,
                ]
            );
        }

        Log::info('Ola Health network seeded successfully', [
            'network_id' => $olaHealth->id,
            'video_states_count' => count(self::VIDEO_STATE_CODES),
            'async_states_count' => max(0, $states->count() - count(self::VIDEO_STATE_CODES)),
        ]);
    }

    private function journeySteps(): array
    {
        return [
            [
                'step_key' => 'checkout',
                'phase' => 'payment',
                'name' => 'Checkout',
                'description' => 'Customer starts Stripe checkout for the selected order.',
                'required' => true,
                'system_managed' => false,
                'order' => 1,
            ],
            [
                'step_key' => 'awaiting_payment_confirmation',
                'phase' => 'payment',
                'name' => 'Awaiting Payment Confirmation',
                'description' => 'System waits for Stripe webhook confirmation after checkout.',
                'required' => true,
                'system_managed' => true,
                'order' => 2,
            ],
            [
                'step_key' => 'dr_network_initializing',
                'phase' => 'dr_network_initialization',
                'name' => 'Preparing Consultation Journey',
                'description' => 'System starts the assigned Ola Health workflow after payment is confirmed.',
                'required' => true,
                'system_managed' => true,
                'order' => 3,
            ],
            [
                'step_key' => 'document_upload',
                'phase' => 'dr_network',
                'name' => 'Upload Documents',
                'description' => 'Upload required identity or clinical documents.',
                'required' => true,
                'system_managed' => false,
                'order' => 4,
            ],
            [
                'step_key' => 'intake_questions',
                'phase' => 'dr_network',
                'name' => 'Answer Medical Questions',
                'description' => 'Answer the Ola Health medical intake questions for the selected flow.',
                'required' => true,
                'system_managed' => false,
                'order' => 5,
            ],
            [
                'step_key' => 'slot_selection',
                'phase' => 'dr_network',
                'name' => 'Select Appointment Time',
                'description' => 'Select a provider slot when the selected Ola Health flow requires scheduling.',
                'required' => false,
                'system_managed' => false,
                'order' => 6,
            ],
            [
                'step_key' => 'review_and_submit',
                'phase' => 'dr_network',
                'name' => 'Review and Submit',
                'description' => 'Review consultation details and submit the case to Ola Health.',
                'required' => true,
                'system_managed' => false,
                'order' => 7,
            ],
            [
                'step_key' => 'awaiting_review',
                'phase' => 'dr_network',
                'name' => 'Awaiting Provider Review',
                'description' => 'System waits while the provider reviews the submitted case.',
                'required' => true,
                'system_managed' => true,
                'order' => 8,
            ],
            [
                'step_key' => 'completed',
                'phase' => 'completed',
                'name' => 'Completed',
                'description' => 'The consultation journey is complete.',
                'required' => false,
                'system_managed' => true,
                'order' => 9,
            ],
            [
                'step_key' => 'failed',
                'phase' => 'failed',
                'name' => 'Failed',
                'description' => 'The journey could not be completed and requires support.',
                'required' => false,
                'system_managed' => true,
                'order' => 10,
            ],
        ];
    }

    private function paymentJourneySteps(int $startOrder): array
    {
        return [
            [
                'step_key' => 'checkout',
                'phase' => 'payment',
                'name' => 'Checkout',
                'description' => 'Customer starts Stripe checkout for the selected order.',
                'required' => true,
                'system_managed' => false,
                'order' => $startOrder,
            ],
            [
                'step_key' => 'awaiting_payment_confirmation',
                'phase' => 'payment',
                'name' => 'Awaiting Payment Confirmation',
                'description' => 'System waits for Stripe webhook confirmation after checkout.',
                'required' => true,
                'system_managed' => true,
                'order' => $startOrder + 1,
            ],
        ];
    }

    private function moneyEnv(string $key): float
    {
        $value = env($key, 0);

        return is_numeric($value) ? max(0, round((float) $value, 2)) : 0.0;
    }

    private function updateOrCreateFlow(
        DrNetwork $network,
        string $legacyFlowKey,
        string $flowKey,
        array $values
    ): NetworkFlowDefinition
    {
        $flow = NetworkFlowDefinition::query()
            ->forNetwork($network->id)
            ->forKey($flowKey)
            ->first();

        if (! $flow) {
            $flow = NetworkFlowDefinition::query()
                ->forNetwork($network->id)
                ->forKey($legacyFlowKey)
                ->first();
        }

        if ($flow) {
            $flow->update(array_merge($values, [
                'dr_network_id' => $network->id,
                'flow_key' => $flowKey,
            ]));

            return $flow->refresh();
        }

        return NetworkFlowDefinition::query()->create(array_merge($values, [
            'dr_network_id' => $network->id,
            'flow_key' => $flowKey,
        ]));
    }

    private function seedConfigValues(DrNetwork $network): void
    {
        $configValues = [
            'auth_token' => [
                'value' => env('OLA_HEALTH_AUTH_TOKEN', 'dummy'),
                'value_type' => DrNetworkConfigValue::TYPE_STRING,
                'is_secret' => true,
                'display_name' => 'Auth Token',
            ],
            'secret_token' => [
                'value' => env('OLA_HEALTH_SECRET_TOKEN', 'ddummy'),
                'value_type' => DrNetworkConfigValue::TYPE_STRING,
                'is_secret' => true,
                'display_name' => 'Secret Token',
            ],
            'tenant' => [
                'value' => env('OLA_HEALTH_TENANT', ''),
                'value_type' => DrNetworkConfigValue::TYPE_STRING,
                'is_secret' => false,
                'display_name' => 'Tenant',
            ],
        ];

        DrNetworkConfigValue::query()
            ->where('dr_network_id', $network->id)
            ->whereNotIn('key', array_keys($configValues))
            ->delete();

        foreach ($configValues as $key => $configValue) {
            DrNetworkConfigValue::query()->updateOrCreate(
                [
                    'dr_network_id' => $network->id,
                    'key' => $key,
                ],
                $configValue
            );
        }
    }

    private function webhookEndpointTokenHash(?DrNetwork $network): string
    {
        $envToken = env('OLA_HEALTH_WEBHOOK_ENDPOINT_TOKEN');

        if (filled($envToken)) {
            return DrNetworkConfigValue::lookupHash((string) $envToken);
        }

        $existingHash = $network?->settings['webhook_endpoint_token_hash'] ?? null;

        if (filled($existingHash)) {
            return (string) $existingHash;
        }

        $existingToken = $network?->configValue('webhook_endpoint_token');

        return DrNetworkConfigValue::lookupHash(
            filled($existingToken) ? (string) $existingToken : (string) Str::uuid()
        );
    }
}
