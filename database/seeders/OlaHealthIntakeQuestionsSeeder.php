<?php

namespace Database\Seeders;

use App\Models\DrNetwork;
use App\Models\NetworkFlowDefinition;
use App\Models\NetworkIntakeQuestion;
use App\Models\NetworkIntakeQuestionSet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class OlaHealthIntakeQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        $olaHealth = DrNetwork::query()->where('slug', 'ola-health')->firstOrFail();
        $asyncFlow = NetworkFlowDefinition::query()
            ->forNetwork($olaHealth->id)
            ->forKey(OlaHealthNetworkSeeder::ASYNC_FLOW_KEY)
            ->firstOrFail();
        $videoFlow = NetworkFlowDefinition::query()
            ->forNetwork($olaHealth->id)
            ->forKey(OlaHealthNetworkSeeder::VIDEO_FLOW_KEY)
            ->firstOrFail();
        // Follow-up async intake questions are temporarily disabled.
        // $followUpFlow = NetworkFlowDefinition::query()
        //     ->forNetwork($olaHealth->id)
        //     ->forKey(OlaHealthNetworkSeeder::FOLLOW_UP_ASYNC_FLOW_KEY)
        //     ->firstOrFail();

        $asyncQuestionSet = NetworkIntakeQuestionSet::query()->updateOrCreate(
            [
                'dr_network_id' => $olaHealth->id,
                'flow_id' => $asyncFlow->id,
                'product_code' => NetworkIntakeQuestionSet::ALL_SCOPE,
                'state_code' => NetworkIntakeQuestionSet::ALL_SCOPE,
                'version' => 1,
            ],
            [
                'set_key' => 'ola_async_general',
                'set_name' => 'Ola Health - Async Consultation Questions',
                'status' => NetworkIntakeQuestionSet::STATUS_PUBLISHED,
                'metadata' => [
                    'network' => 'ola_health',
                    'flow_key' => OlaHealthNetworkSeeder::ASYNC_FLOW_KEY,
                ],
            ]
        );

        $videoQuestionSet = NetworkIntakeQuestionSet::query()->updateOrCreate(
            [
                'dr_network_id' => $olaHealth->id,
                'flow_id' => $videoFlow->id,
                'product_code' => NetworkIntakeQuestionSet::ALL_SCOPE,
                'state_code' => NetworkIntakeQuestionSet::ALL_SCOPE,
                'version' => 1,
            ],
            [
                'set_key' => 'ola_video_general',
                'set_name' => 'Ola Health - Video Consultation Questions',
                'status' => NetworkIntakeQuestionSet::STATUS_PUBLISHED,
                'metadata' => [
                    'network' => 'ola_health',
                    'flow_key' => OlaHealthNetworkSeeder::VIDEO_FLOW_KEY,
                ],
            ]
        );

        // $followUpQuestionSet = NetworkIntakeQuestionSet::query()->updateOrCreate(
        //     [
        //         'dr_network_id' => $olaHealth->id,
        //         'flow_id' => $followUpFlow->id,
        //         'product_code' => NetworkIntakeQuestionSet::ALL_SCOPE,
        //         'state_code' => NetworkIntakeQuestionSet::ALL_SCOPE,
        //         'version' => 1,
        //     ],
        //     [
        //         'set_key' => 'ola_follow_up_async_general',
        //         'set_name' => 'Ola Health - Follow-up Async Consultation Questions',
        //         'status' => NetworkIntakeQuestionSet::STATUS_PUBLISHED,
        //         'metadata' => [
        //             'network' => 'ola_health',
        //             'flow_key' => OlaHealthNetworkSeeder::FOLLOW_UP_ASYNC_FLOW_KEY,
        //         ],
        //     ]
        // );

        $this->syncQuestions($asyncQuestionSet, includeInsuranceQuestion: false);
        $this->syncQuestions($videoQuestionSet, includeInsuranceQuestion: true);
        // $this->syncQuestions($followUpQuestionSet, includeInsuranceQuestion: false);

        Log::info('Ola Health intake questions seeded successfully');
    }

    private function syncQuestions(NetworkIntakeQuestionSet $questionSet, bool $includeInsuranceQuestion): void
    {
        $questions = [
            [
                'question_key' => 'ola_medical_history',
                'question_text' => 'Do you have any chronic medical conditions?',
                'help_text' => 'E.g., diabetes, hypertension, asthma, arthritis, heart disease.',
                'sort_order' => 1,
                'input_type' => NetworkIntakeQuestion::INPUT_RADIO,
                'options' => $this->yesNoNotSureOptions(),
                'is_required' => true,
                'is_conditional' => false,
                'condition_rules' => null,
                'network_field_mapping' => 'medical_history',
                'network_validation' => [
                    'blocking_rules' => [
                        [
                            'rule_key' => 'ola_chronic_condition_ineligible',
                            'reason' => 'medical_ineligible',
                            'message' => 'You are not eligible for this product based on your medical history.',
                            'conditions' => [
                                [
                                    'source' => 'answers.ola_medical_history',
                                    'operator' => 'equals',
                                    'value' => 'yes',
                                ],
                            ],
                        ],
                    ],
                ],
                'metadata' => null,
            ],
            [
                'question_key' => 'ola_current_medications',
                'question_text' => 'What medications are you currently taking?',
                'help_text' => 'List all current medications including dosages.',
                'sort_order' => 2,
                'input_type' => NetworkIntakeQuestion::INPUT_LONG_TEXT,
                'options' => null,
                'is_required' => false,
                'is_conditional' => true,
                'condition_rules' => [
                    [
                        'when' => 'ola_medical_history',
                        'equals' => 'yes',
                    ],
                ],
                'network_field_mapping' => 'current_medications',
                'metadata' => [
                    'min_length' => 5,
                    'max_length' => 1000,
                    'placeholder' => 'E.g., Metformin 500mg twice daily.',
                ],
            ],
            [
                'question_key' => 'ola_allergies',
                'question_text' => 'Do you have any drug allergies?',
                'help_text' => 'Include any allergic reactions to medications.',
                'sort_order' => 3,
                'input_type' => NetworkIntakeQuestion::INPUT_RADIO,
                'options' => $this->yesNoNotSureOptions(),
                'is_required' => true,
                'is_conditional' => false,
                'condition_rules' => null,
                'network_field_mapping' => 'drug_allergies_yes_no',
                'metadata' => null,
            ],
            [
                'question_key' => 'ola_allergy_details',
                'question_text' => 'What are you allergic to?',
                'help_text' => 'List the specific medications or substances.',
                'sort_order' => 4,
                'input_type' => NetworkIntakeQuestion::INPUT_LONG_TEXT,
                'options' => null,
                'is_required' => true,
                'is_conditional' => true,
                'condition_rules' => [
                    [
                        'when' => 'ola_allergies',
                        'equals' => 'yes',
                    ],
                ],
                'network_field_mapping' => 'drug_allergies_details',
                'metadata' => [
                    'min_length' => 3,
                    'max_length' => 500,
                    'placeholder' => 'E.g., Penicillin causes hives.',
                ],
            ],
            [
                'question_key' => 'ola_chief_complaint',
                'question_text' => 'What is your main health concern today?',
                'help_text' => 'Describe your primary symptoms or reason for this consultation.',
                'sort_order' => 5,
                'input_type' => NetworkIntakeQuestion::INPUT_LONG_TEXT,
                'options' => null,
                'is_required' => true,
                'is_conditional' => false,
                'condition_rules' => null,
                'network_field_mapping' => 'chief_complaint',
                'metadata' => [
                    'min_length' => 10,
                    'max_length' => 1000,
                    'placeholder' => 'E.g., I have had a persistent cough for 3 days.',
                ],
            ],
            [
                'question_key' => 'ola_symptom_duration',
                'question_text' => 'How long have you had these symptoms?',
                'help_text' => 'Estimated duration of your symptoms.',
                'sort_order' => 6,
                'input_type' => NetworkIntakeQuestion::INPUT_SELECT,
                'options' => [
                    ['id' => 'less_24h', 'label' => 'Less than 24 hours', 'value' => 'less_24h'],
                    ['id' => '1_3_days', 'label' => '1-3 days', 'value' => '1_3_days'],
                    ['id' => '3_7_days', 'label' => '3-7 days', 'value' => '3_7_days'],
                    ['id' => '1_2_weeks', 'label' => '1-2 weeks', 'value' => '1_2_weeks'],
                    ['id' => 'over_2_weeks', 'label' => 'Over 2 weeks', 'value' => 'over_2_weeks'],
                ],
                'is_required' => true,
                'is_conditional' => false,
                'condition_rules' => null,
                'network_field_mapping' => 'symptom_duration',
                'metadata' => null,
            ],
            [
                'question_key' => 'ola_severity',
                'question_text' => 'How severe are your symptoms?',
                'help_text' => 'Rate your symptoms on a scale.',
                'sort_order' => 7,
                'input_type' => NetworkIntakeQuestion::INPUT_SELECT,
                'options' => [
                    ['id' => 'mild', 'label' => 'Mild', 'value' => 'mild'],
                    ['id' => 'moderate', 'label' => 'Moderate', 'value' => 'moderate'],
                    ['id' => 'severe', 'label' => 'Severe', 'value' => 'severe'],
                ],
                'is_required' => true,
                'is_conditional' => false,
                'condition_rules' => null,
                'network_field_mapping' => 'symptom_severity',
                'metadata' => null,
            ],
            [
                'question_key' => 'ola_treatment_tried',
                'question_text' => 'Have you tried any treatment for these symptoms?',
                'help_text' => 'Include home remedies, over-the-counter medications, etc.',
                'sort_order' => 8,
                'input_type' => NetworkIntakeQuestion::INPUT_RADIO,
                'options' => $this->yesNoOptions(),
                'is_required' => true,
                'is_conditional' => false,
                'condition_rules' => null,
                'network_field_mapping' => 'treatment_tried',
                'metadata' => null,
            ],
            [
                'question_key' => 'ola_treatment_details',
                'question_text' => 'What treatment did you try and what was the result?',
                'help_text' => 'Describe what you took or used and whether it helped.',
                'sort_order' => 9,
                'input_type' => NetworkIntakeQuestion::INPUT_LONG_TEXT,
                'options' => null,
                'is_required' => true,
                'is_conditional' => true,
                'condition_rules' => [
                    [
                        'when' => 'ola_treatment_tried',
                        'equals' => 'yes',
                    ],
                ],
                'network_field_mapping' => 'treatment_tried_details',
                'metadata' => [
                    'min_length' => 5,
                    'max_length' => 500,
                    'placeholder' => 'E.g., Took ibuprofen, helped temporarily.',
                ],
            ],
            [
                'question_key' => 'ola_pregnancy_status',
                'question_text' => 'Are you pregnant, planning to become pregnant, or breastfeeding?',
                'help_text' => 'Some medications and treatment plans are not appropriate during pregnancy or breastfeeding.',
                'sort_order' => 10,
                'input_type' => NetworkIntakeQuestion::INPUT_RADIO,
                'options' => $this->yesNoNotSureOptions(),
                'is_required' => true,
                'is_conditional' => true,
                'condition_rules' => [
                    [
                        'source' => 'patient.gender',
                        'operator' => 'equals',
                        'value' => 'female',
                    ],
                ],
                'network_field_mapping' => 'pregnancy_status',
                'network_validation' => [
                    'blocking_rules' => [
                        [
                            'rule_key' => 'ola_pregnancy_ineligible',
                            'reason' => 'pregnancy_ineligible',
                            'message' => 'You are not eligible for this product while pregnant, planning pregnancy, or breastfeeding.',
                            'conditions' => [
                                [
                                    'source' => 'answers.ola_pregnancy_status',
                                    'operator' => 'in',
                                    'value' => ['yes', 'not_sure'],
                                ],
                            ],
                        ],
                    ],
                ],
                'metadata' => null,
            ],
            [
                'question_key' => 'ola_male_reproductive_history',
                'question_text' => 'Have you had any prostate or reproductive health conditions the provider should know about?',
                'help_text' => 'Include prostate conditions, fertility treatment, or related medications if applicable.',
                'sort_order' => 11,
                'input_type' => NetworkIntakeQuestion::INPUT_RADIO,
                'options' => $this->yesNoOptions(),
                'is_required' => true,
                'is_conditional' => true,
                'condition_rules' => [
                    [
                        'source' => 'patient.gender',
                        'operator' => 'equals',
                        'value' => 'male',
                    ],
                ],
                'network_field_mapping' => 'male_reproductive_history',
                'metadata' => null,
            ],
        ];

        if ($includeInsuranceQuestion) {
            $questions[] = [
                'question_key' => 'ola_has_insurance',
                'question_text' => 'Do you have health insurance?',
                'help_text' => 'We can collect insurance information when required for this consultation.',
                'sort_order' => 12,
                'input_type' => NetworkIntakeQuestion::INPUT_RADIO,
                'options' => $this->yesNoOptions(),
                'is_required' => true,
                'is_conditional' => false,
                'condition_rules' => null,
                'network_field_mapping' => 'has_insurance',
                'metadata' => null,
            ];
        }

        foreach ($questions as $question) {
            NetworkIntakeQuestion::query()->updateOrCreate(
                [
                    'question_set_id' => $questionSet->id,
                    'question_key' => $question['question_key'],
                ],
                array_merge([
                    'validation_rules' => null,
                    'network_validation' => null,
                ], $question, ['is_active' => true])
            );
        }
    }

    private function yesNoOptions(): array
    {
        return [
            ['id' => 'yes', 'label' => 'Yes', 'value' => 'yes'],
            ['id' => 'no', 'label' => 'No', 'value' => 'no'],
        ];
    }

    private function yesNoNotSureOptions(): array
    {
        return [
            ['id' => 'yes', 'label' => 'Yes', 'value' => 'yes'],
            ['id' => 'no', 'label' => 'No', 'value' => 'no'],
            ['id' => 'not_sure', 'label' => 'Not Sure', 'value' => 'not_sure'],
        ];
    }
}
