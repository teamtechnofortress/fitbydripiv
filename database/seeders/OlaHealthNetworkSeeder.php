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
                ],
                'metadata' => [
                    'supports_video' => true,
                    'supports_async' => true,
                    'requires_provider_scheduling' => true,
                    'document_verification' => 'liveness_required_for_video',
                ],
                'feature_flags' => [
                    'video_consultation' => true,
                    'async_review' => true,
                    'prescription_delivery' => false,
                    'webhook_enabled' => true,
                    'polling_enabled' => true,
                ],
                'config_version' => 1,
            ]
        );

        $this->seedConfigValues($olaHealth);

        $asyncFlow = NetworkFlowDefinition::query()->updateOrCreate(
            ['flow_key' => 'async_review'],
            [
                'name' => 'Async Consultation Review',
                'description' => 'Patient submits intake forms, then provider reviews and decides asynchronously.',
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
                    [
                        'step_key' => 'review_and_submit',
                        'name' => 'Review and Submit',
                        'description' => 'Review information and submit to provider.',
                        'required' => true,
                        'order' => 3,
                    ],
                    [
                        'step_key' => 'provider_review',
                        'name' => 'Provider Review',
                        'description' => 'Wait for provider review and decision.',
                        'required' => true,
                        'order' => 4,
                    ],
                ],
                'is_active' => true,
            ]
        );

        $videoFlow = NetworkFlowDefinition::query()->updateOrCreate(
            ['flow_key' => 'video_consultation'],
            [
                'name' => 'Video Consultation',
                'description' => 'Patient submits intake, selects a provider slot, then attends a video consultation.',
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
                    [
                        'step_key' => 'slot_selection',
                        'name' => 'Select Appointment Time',
                        'description' => 'Choose an available provider time for video consultation.',
                        'required' => true,
                        'order' => 3,
                    ],
                    [
                        'step_key' => 'review_and_submit',
                        'name' => 'Confirm Appointment',
                        'description' => 'Confirm appointment details and submit.',
                        'required' => true,
                        'order' => 4,
                    ],
                    [
                        'step_key' => 'video_consultation',
                        'name' => 'Video Consultation',
                        'description' => 'Participate in a video call with a provider.',
                        'required' => true,
                        'order' => 5,
                    ],
                ],
                'is_active' => true,
            ]
        );

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

    private function seedConfigValues(DrNetwork $network): void
    {
        $network->loadMissing('configValues');
        $webhookEndpointToken = $this->webhookEndpointToken($network);

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
            'service_key' => [
                'value' => env('OLA_HEALTH_SERVICE_KEY', 'fitbyshot-semaglutide-injection'),
                'value_type' => DrNetworkConfigValue::TYPE_STRING,
                'is_secret' => false,
                'display_name' => 'Service Key',
            ],
            'service_id' => [
                'value' => env('OLA_HEALTH_SERVICE_ID', '123'),
                'value_type' => DrNetworkConfigValue::TYPE_INTEGER,
                'is_secret' => false,
                'display_name' => 'Service ID',
            ],
            'session_type' => [
                'value' => env('OLA_HEALTH_SESSION_TYPE', 'initial'),
                'value_type' => DrNetworkConfigValue::TYPE_STRING,
                'is_secret' => false,
                'display_name' => 'Session Type',
            ],
            'webhook_enabled' => [
                'value' => true,
                'value_type' => DrNetworkConfigValue::TYPE_BOOLEAN,
                'is_secret' => false,
                'display_name' => 'Webhook Enabled',
            ],
            'webhook_endpoint_token' => [
                'value' => $webhookEndpointToken,
                'lookup_hash' => DrNetworkConfigValue::lookupHash($webhookEndpointToken),
                'value_type' => DrNetworkConfigValue::TYPE_STRING,
                'is_secret' => true,
                'display_name' => 'Webhook Endpoint Token',
                'description' => 'Opaque token used in the generic Dr Network webhook URL.',
            ],
            'webhook_signatures_enabled' => [
                'value' => false,
                'value_type' => DrNetworkConfigValue::TYPE_BOOLEAN,
                'is_secret' => false,
                'display_name' => 'Webhook Signatures Enabled',
                'description' => 'Ola Health currently has no confirmed webhook signature contract.',
            ],
        ];

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

    private function webhookEndpointToken(DrNetwork $network): string
    {
        return env('OLA_HEALTH_WEBHOOK_ENDPOINT_TOKEN')
            ?: $network->configValue('webhook_endpoint_token')
            ?: (string) Str::uuid();
    }
}
