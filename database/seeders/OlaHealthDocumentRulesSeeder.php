<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\DrNetwork;
use App\Models\NetworkDocumentRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class OlaHealthDocumentRulesSeeder extends Seeder
{
    public function run(): void
    {
        $olaHealth = DrNetwork::query()->where('slug', 'ola-health')->firstOrFail();
        $passport = DocumentType::query()->where('key', 'passport')->firstOrFail();
        $license = DocumentType::query()->where('key', 'driver_license')->firstOrFail();
        // $selfie = DocumentType::query()->where('key', 'selfie')->firstOrFail();

        NetworkDocumentRule::query()->updateOrCreate(
            ['rule_key' => 'ola_async_identity'],
            [
                'dr_network_id' => $olaHealth->id,
                'flow_key' => OlaHealthNetworkSeeder::ASYNC_FLOW_KEY,
                'state_code' => null,
                'product_code' => null,
                'rule_name' => 'Identity Verification (Async)',
                'priority' => 1,
                'requirement_type' => NetworkDocumentRule::REQUIREMENT_IDENTITY,
                'operator' => NetworkDocumentRule::OPERATOR_ANY,
                'document_ids' => [$passport->id, $license->id],
                'is_required' => true,
                'conditions' => [
                    'liveness_required' => false,
                    'max_age_days' => 3650,
                ],
                'error_message' => 'Please upload a valid government-issued ID (Passport or Driver License).',
                'help_text' => 'We accept Passport or Driver License. The document must not be expired.',
                'is_active' => true,
            ]
        );

        NetworkDocumentRule::query()->updateOrCreate(
            ['rule_key' => 'ola_video_identity'],
            [
                'dr_network_id' => $olaHealth->id,
                'flow_key' => OlaHealthNetworkSeeder::VIDEO_FLOW_KEY,
                'state_code' => null,
                'product_code' => null,
                'rule_name' => 'Identity with Liveness Verification (Video)',
                'priority' => 1,
                'requirement_type' => NetworkDocumentRule::REQUIREMENT_IDENTITY,
                'operator' => NetworkDocumentRule::OPERATOR_ANY,
                'document_ids' => [$passport->id, $license->id],
                'is_required' => true,
                'conditions' => [
                    'liveness_required' => true,
                    'liveness_score_min' => 0.95,
                    'max_age_days' => 3650,
                ],
                'error_message' => 'Please upload a clear photo of your government-issued ID.',
                'help_text' => 'We need a clear photo of your Passport or Driver License.',
                'is_active' => true,
            ]
        );

        // NetworkDocumentRule::query()->updateOrCreate(
        //     ['rule_key' => 'ola_video_selfie'],
        //     [
        //         'dr_network_id' => $olaHealth->id,
        //         'flow_key' => OlaHealthNetworkSeeder::VIDEO_FLOW_KEY,
        //         'state_code' => null,
        //         'product_code' => null,
        //         'rule_name' => 'Selfie Verification (Video)',
        //         'priority' => 2,
        //         'requirement_type' => NetworkDocumentRule::REQUIREMENT_VERIFICATION,
        //         'operator' => NetworkDocumentRule::OPERATOR_EXACT,
        //         'document_ids' => [$selfie->id],
        //         'is_required' => true,
        //         'conditions' => [
        //             'liveness_required' => true,
        //             'liveness_score_min' => 0.95,
        //         ],
        //         'error_message' => 'Selfie verification failed. Please try again with better lighting.',
        //         'help_text' => 'Take a clear selfie in good lighting. Your face must be visible.',
        //         'is_active' => true,
        //     ]
        // );

        Log::info('Ola Health document rules seeded successfully');
    }
}
