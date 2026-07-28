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
    private const GLP1_PRODUCTS = [
        'semaglutide' => 'Semaglutide Injection',
        'tirzepatide' => 'Tirzepatide Injection',
    ];

    private const NAD_PRODUCTS = [
        'nad-therapy' => 'NAD+',
    ];

    private const GLUTATHIONE_MIC_B12_PRODUCTS = [
        'glutathione' => 'Glutathione',
        'b12-injection' => 'B-Complex / B12',
    ];

    public function run(): void
    {
        $olaHealth = DrNetwork::query()->where('slug', 'ola-health')->firstOrFail();
        $flows = [
            OlaHealthNetworkSeeder::ASYNC_FLOW_KEY => NetworkFlowDefinition::query()
                ->forNetwork($olaHealth->id)
                ->forKey(OlaHealthNetworkSeeder::ASYNC_FLOW_KEY)
                ->firstOrFail(),
            OlaHealthNetworkSeeder::VIDEO_FLOW_KEY => NetworkFlowDefinition::query()
                ->forNetwork($olaHealth->id)
                ->forKey(OlaHealthNetworkSeeder::VIDEO_FLOW_KEY)
                ->firstOrFail(),
        ];

        $this->archiveLegacyGenericSets($olaHealth);

        foreach ($flows as $flowKey => $flow) {
            foreach (self::GLP1_PRODUCTS as $productSlug => $productName) {
                $set = $this->questionSet(
                    $olaHealth,
                    $flow,
                    $productSlug,
                    "ola_{$this->flowAlias($flowKey)}_glp1_{$productSlug}",
                    "{$productName} Initial GLP-1 Questions",
                    'glp1_initial'
                );

                $this->syncQuestions($set, $this->glp1InitialQuestions($productSlug));
            }

            foreach (self::NAD_PRODUCTS as $productSlug => $productName) {
                $set = $this->questionSet(
                    $olaHealth,
                    $flow,
                    $productSlug,
                    "ola_{$this->flowAlias($flowKey)}_nad_{$productSlug}",
                    "{$productName} Initial Questions",
                    'nad_initial'
                );

                $this->syncQuestions($set, $this->nadInitialQuestions());
            }

            foreach (self::GLUTATHIONE_MIC_B12_PRODUCTS as $productSlug => $productName) {
                $set = $this->questionSet(
                    $olaHealth,
                    $flow,
                    $productSlug,
                    "ola_{$this->flowAlias($flowKey)}_wellness_{$productSlug}",
                    "{$productName} Initial Questions",
                    'glutathione_mic_b12_initial'
                );

                $this->syncQuestions($set, $this->glutathioneMicB12InitialQuestions($productSlug));
            }
        }

        Log::info('Ola Health protocol intake questions seeded successfully');
    }

    private function questionSet(
        DrNetwork $network,
        NetworkFlowDefinition $flow,
        string $productSlug,
        string $setKey,
        string $setName,
        string $protocol
    ): NetworkIntakeQuestionSet {
        return NetworkIntakeQuestionSet::query()->updateOrCreate(
            [
                'dr_network_id' => $network->id,
                'flow_id' => $flow->id,
                'product_code' => $productSlug,
                'state_code' => NetworkIntakeQuestionSet::ALL_SCOPE,
                'version' => 1,
            ],
            [
                'set_key' => $setKey,
                'set_name' => $setName,
                'status' => NetworkIntakeQuestionSet::STATUS_PUBLISHED,
                'metadata' => [
                    'network' => 'ola_health',
                    'flow_key' => $flow->flow_key,
                    'protocol' => $protocol,
                    'source_documents' => [
                        'Elite Care GLP-1 Protocol',
                        'Glutathione MIC B12 Sept 25',
                        'NAD+ Protocol Updated Dec 15 2025',
                    ],
                    'follow_up_disabled' => true,
                ],
            ]
        );
    }

    private function archiveLegacyGenericSets(DrNetwork $network): void
    {
        NetworkIntakeQuestionSet::query()
            ->where('dr_network_id', $network->id)
            ->whereIn('set_key', [
                'ola_async_general',
                'ola_video_general',
                'ola_follow_up_async_general',
            ])
            ->update(['status' => NetworkIntakeQuestionSet::STATUS_ARCHIVED]);
    }

    private function syncQuestions(NetworkIntakeQuestionSet $questionSet, array $questions): void
    {
        $activeKeys = collect($questions)->pluck('question_key')->all();

        foreach ($questions as $question) {
            NetworkIntakeQuestion::query()->updateOrCreate(
                [
                    'question_set_id' => $questionSet->id,
                    'question_key' => $question['question_key'],
                ],
                array_merge([
                    'help_text' => null,
                    'options' => null,
                    'validation_rules' => null,
                    'is_conditional' => false,
                    'condition_rules' => null,
                    'network_field_mapping' => $question['question_text'],
                    'network_validation' => null,
                    'metadata' => null,
                ], $question, ['is_active' => true])
            );
        }

        NetworkIntakeQuestion::query()
            ->where('question_set_id', $questionSet->id)
            ->whereNotIn('question_key', $activeKeys)
            ->update(['is_active' => false]);
    }

    private function glp1InitialQuestions(string $productSlug): array
    {
        $productName = $this->glp1ProductName($productSlug);

        $questions = [
            $this->question(
                'glp1_telehealth_consent',
                'Do you consent to telehealth visits?',
                1,
                NetworkIntakeQuestion::INPUT_CHECKBOX,
                $this->consentCheckboxOptions(),
                metadata: ['protocol_section' => 'GLP-1 Initial Q1']
            ),
            $this->question(
                'glp1_height_feet',
                'Height - feet',
                2,
                NetworkIntakeQuestion::INPUT_NUMBER,
                metadata: [
                    'unit' => 'ft',
                    'ui_component' => 'bmi_calculator',
                    'ui_page_key' => 'body_metrics',
                    'ui_group_key' => 'glp1_body_metrics',
                    'ui_group_label' => 'Body metrics',
                    'ui_group_role' => 'height_feet',
                ]
            ),
            $this->question(
                'glp1_height_inches',
                'Height - inches',
                3,
                NetworkIntakeQuestion::INPUT_NUMBER,
                metadata: [
                    'unit' => 'in',
                    'ui_component' => 'bmi_calculator',
                    'ui_page_key' => 'body_metrics',
                    'ui_group_key' => 'glp1_body_metrics',
                    'ui_group_label' => 'Body metrics',
                    'ui_group_role' => 'height_inches',
                ]
            ),
            $this->question(
                'glp1_weight_lbs',
                'Current weight in pounds',
                4,
                NetworkIntakeQuestion::INPUT_NUMBER,
                metadata: [
                    'unit' => 'lbs',
                    'ui_component' => 'bmi_calculator',
                    'ui_page_key' => 'body_metrics',
                    'ui_group_key' => 'glp1_body_metrics',
                    'ui_group_label' => 'Body metrics',
                    'ui_group_role' => 'weight_lbs',
                ]
            ),
            $this->question(
                'glp1_bmi',
                'BMI',
                5,
                NetworkIntakeQuestion::INPUT_NUMBER,
                helpText: 'BMI should be calculated from height and weight. BMI below 20 is not eligible.',
                networkValidation: [
                    'blocking_rules' => [
                        [
                            'rule_key' => 'glp1_bmi_under_20',
                            'reason' => 'bmi_not_eligible',
                            'hard_stop_type' => 'refer_out',
                            'message' => 'Based on your height and weight, your calculated BMI does not meet the eligibility criteria for this treatment through this telehealth program.',
                            'conditions' => [
                                ['source' => 'answers.glp1_bmi', 'operator' => 'less_than', 'value' => 20],
                            ],
                        ],
                    ],
                ],
                metadata: [
                    'frontend_visible' => true,
                    'read_only' => true,
                    'unit' => 'bmi',
                    'ui_component' => 'bmi_calculator',
                    'ui_page_key' => 'body_metrics',
                    'ui_group_key' => 'glp1_body_metrics',
                    'ui_group_label' => 'Body metrics',
                    'ui_group_role' => 'bmi',
                    'auto_fill' => NetworkIntakeQuestion::AUTO_FILL_CALCULATED_BMI,
                    'calculation_required' => true,
                    'calculated_from' => [
                        'glp1_height_feet',
                        'glp1_height_inches',
                        'glp1_weight_lbs',
                    ],
                    'standard_protocol_min_bmi' => 23,
                    'microdosing_bmi_range' => ['min' => 20, 'max' => 22.9],
                ]
            ),
            $this->question(
                'glp1_exclusion_conditions',
                'Do you currently have, or have you ever been diagnosed with, any of the following?',
                6,
                NetworkIntakeQuestion::INPUT_MULTISELECT,
                $this->options([
                    'mtc_history' => 'Personal or family history of medullary thyroid carcinoma (MTC)',
                    'men2_history' => 'MEN2 or family history of MEN2',
                    'glp1_allergy' => 'Known allergy or hypersensitivity to GLP medications',
                    'type_1_diabetes' => 'Type 1 diabetes',
                    'poorly_controlled_type_2_diabetes' => 'Poorly controlled Type 2 diabetes',
                    'non_metformin_diabetes_meds' => 'Diabetes medications other than metformin',
                    'diabetic_retinopathy' => 'Diabetic retinopathy',
                    'heart_failure' => 'Heart failure or CHF',
                    'uncontrolled_thyroid' => 'Uncontrolled or untreated thyroid disease',
                    'severe_liver_disease' => 'Cirrhosis or severe liver disease',
                    'kidney_disease' => 'Kidney disease',
                    'pancreatitis' => 'History of pancreatitis',
                    'gallbladder_intact_symptoms' => 'Gallbladder problems with gallbladder not removed',
                    'severe_gi_disorder' => 'Gastroparesis, bowel obstruction, severe GERD, cyclic vomiting, or severe chronic constipation',
                    'active_cancer' => 'Actively treated cancer',
                    'restrictive_eating_disorder' => 'Restrictive or purging eating disorder',
                    'severe_mental_health' => 'Severe or uncontrolled mental health condition',
                    'none' => 'None of the above',
                ]),
                networkValidation: $this->blockOnAnySelectedExceptNone(
                    'glp1_exclusion_conditions',
                    [
                        'mtc_history',
                        'men2_history',
                        'glp1_allergy',
                        'type_1_diabetes',
                        'poorly_controlled_type_2_diabetes',
                        'non_metformin_diabetes_meds',
                        'diabetic_retinopathy',
                        'heart_failure',
                        'uncontrolled_thyroid',
                        'severe_liver_disease',
                        'kidney_disease',
                        'pancreatitis',
                        'gallbladder_intact_symptoms',
                        'severe_gi_disorder',
                        'active_cancer',
                        'restrictive_eating_disorder',
                        'severe_mental_health',
                    ],
                    'glp1_contraindication',
                    'Based on this medical history, this treatment requires in-person care.'
                )
            ),
            $this->question(
                'glp1_substance_use_disorder',
                'Do you have an active substance use disorder or are you in active treatment for one?',
                7,
                NetworkIntakeQuestion::INPUT_RADIO,
                $this->yesNoOptions(),
                networkValidation: $this->blockOnEquals('glp1_substance_use_disorder', 'yes', 'glp1_substance_use_refer_out', 'This requires in-person care before GLP treatment can proceed.', 'refer_out')
            ),
            $this->question(
                'glp1_alcohol_frequency',
                'How often do you drink alcohol?',
                8,
                NetworkIntakeQuestion::INPUT_SELECT,
                $this->options([
                    'never' => 'Never or almost never',
                    'up_to_3_week' => '3 or fewer drinks per week',
                    '3_5_week' => '3-5 drinks per week',
                    '5_7_week' => '5-7 drinks per week',
                    'more_than_7_week' => 'More than 7 drinks per week',
                ]),
                networkValidation: $this->blockOnEquals('glp1_alcohol_frequency', 'more_than_7_week', 'glp1_alcohol_refer_out', 'Alcohol intake at this level requires in-person care before treatment can proceed.', 'refer_out')
            ),
            $this->question(
                'glp1_pregnant',
                'Are you currently pregnant?',
                9,
                NetworkIntakeQuestion::INPUT_RADIO,
                $this->yesNoOptions(),
                isConditional: true,
                conditionRules: $this->femaleCondition(),
                networkValidation: $this->blockOnEquals('glp1_pregnant', 'yes', 'glp1_pregnancy_refer_out', 'GLP medications cannot be used during pregnancy. Please seek in-person care.', 'refer_out')
            ),
            $this->question(
                'glp1_breastfeeding',
                'Are you currently breastfeeding or bottle-feeding with breast milk?',
                10,
                NetworkIntakeQuestion::INPUT_RADIO,
                $this->yesNoOptions(),
                isConditional: true,
                conditionRules: $this->femaleCondition(),
                networkValidation: $this->blockOnEquals('glp1_breastfeeding', 'yes', 'glp1_breastfeeding_refer_out', 'GLP medications should not be prescribed while breastfeeding through this flow.', 'refer_out')
            ),
            $this->question(
                'glp1_planning_pregnancy',
                'Are you planning to become pregnant within the next 2 months?',
                11,
                NetworkIntakeQuestion::INPUT_RADIO,
                $this->yesNoOptions(),
                isConditional: true,
                conditionRules: $this->femaleCondition(),
                networkValidation: $this->blockOnEquals('glp1_planning_pregnancy', 'yes', 'glp1_planned_pregnancy_refer_out', 'GLP medications should be stopped before planned pregnancy. Please seek in-person care.', 'refer_out')
            ),
            $this->question('glp1_current_medications', 'List all medications you currently take, including supplements.', 12, NetworkIntakeQuestion::INPUT_LONG_TEXT),
            $this->question('glp1_allergies', 'List any allergies you have, including drug, food, or other allergies.', 13, NetworkIntakeQuestion::INPUT_LONG_TEXT),
            $this->question('glp1_oral_contraceptive', 'Do you currently take an oral contraceptive birth control pill?', 14, NetworkIntakeQuestion::INPUT_RADIO, $this->yesNoOptions(), isConditional: true, conditionRules: $this->femaleCondition()),
            $this->question(
                'glp1_ocp_acknowledgment',
                'Oral contraceptive acknowledgment',
                15,
                NetworkIntakeQuestion::INPUT_CHECKBOX,
                $this->acknowledgmentCheckboxOptions(),
                helpText: 'Tirzepatide may reduce oral hormonal contraceptive bioavailability. Use a non-oral method or add a barrier method for 4 weeks after initiation and each dose increase.',
                isConditional: true,
                conditionRules: [['source' => 'answers.glp1_oral_contraceptive', 'operator' => 'equals', 'value' => 'yes']]
            ),
            $this->question('glp1_thyroid_medication', 'Do you currently take thyroid hormone medication such as levothyroxine, Synthroid, Levoxyl, or Armour Thyroid?', 16, NetworkIntakeQuestion::INPUT_RADIO, $this->yesNoOptions()),
            $this->question(
                'glp1_levothyroxine_acknowledgment',
                'Levothyroxine acknowledgment',
                17,
                NetworkIntakeQuestion::INPUT_CHECKBOX,
                $this->acknowledgmentCheckboxOptions(),
                helpText: 'Oral semaglutide and oral compounded tirzepatide may increase levothyroxine exposure. Thyroid monitoring may be needed.',
                isConditional: true,
                conditionRules: [['source' => 'answers.glp1_thyroid_medication', 'operator' => 'equals', 'value' => 'yes']]
            ),
            $this->question('glp1_warfarin', 'Do you currently take Warfarin or Coumadin?', 18, NetworkIntakeQuestion::INPUT_RADIO, $this->yesNoOptions()),
            $this->question(
                'glp1_warfarin_acknowledgment',
                'Warfarin acknowledgment',
                19,
                NetworkIntakeQuestion::INPUT_CHECKBOX,
                $this->acknowledgmentCheckboxOptions(),
                helpText: 'GLP medications may affect INR variability. Inform the clinician who manages Warfarin.',
                isConditional: true,
                conditionRules: [['source' => 'answers.glp1_warfarin', 'operator' => 'equals', 'value' => 'yes']]
            ),
            $this->question(
                'glp1_contraception_method',
                'What form of contraception are you currently using?',
                20,
                NetworkIntakeQuestion::INPUT_SELECT,
                $this->contraceptionOptions(),
                isConditional: true,
                conditionRules: $this->femaleCondition()
            ),
            $this->question(
                'glp1_contraception_acknowledgment',
                'Contraception acknowledgment',
                21,
                NetworkIntakeQuestion::INPUT_CHECKBOX,
                $this->acknowledgmentCheckboxOptions(),
                helpText: 'GLP medications may cause fetal harm. Use a reliable method of contraception while taking this medication.',
                isConditional: true,
                conditionRules: [['source' => 'answers.glp1_contraception_method', 'operator' => 'in', 'value' => ['none', 'barrier']]]
            ),
            $this->question(
                'glp1_compounding_concerns',
                'Which of the following apply to you regarding compounded medication eligibility?',
                22,
                NetworkIntakeQuestion::INPUT_MULTISELECT,
                $this->options([
                    'prior_severe_brand_side_effects' => 'Severe side effects with brand-name tirzepatide or semaglutide in the past',
                    'fatigue_low_energy' => 'Fatigue or low energy concern',
                    'vegan_vegetarian' => 'Vegan or vegetarian diet',
                    'mental_clarity' => 'Concerned about decreased mental clarity or performance',
                    'skin_elasticity' => 'Concerned about loose or saggy skin from weight loss',
                    'nausea_gi_side_effects' => 'Concerned about nausea or GI side effects',
                    'fatty_liver' => 'History of fatty liver disease',
                    'muscle_wasting' => 'Concerned about muscle wasting during weight loss',
                    'none' => 'None of the above',
                ]),
                networkValidation: $this->blockOnEquals('glp1_compounding_concerns', 'none', 'glp1_compounding_not_justified', 'Based on your answers, you are not eligible for a compounded form of this medication at this time.', 'refer_out'),
                metadata: [
                    'clinical_rationale_mapping' => [
                        'fatigue_low_energy' => ['B12', 'B3/niacin'],
                        'vegan_vegetarian' => ['B12'],
                        'mental_clarity' => ['B12'],
                        'nausea_gi_side_effects' => ['B3/niacin', 'glycine'],
                        'fatty_liver' => ['L-carnitine'],
                        'muscle_wasting' => ['L-carnitine', 'glycine'],
                        'skin_elasticity' => ['glycine'],
                    ],
                ]
            ),
            $this->question('glp1_recent_use', "Are you currently using {$productName}, or have you taken it in the last 4 weeks?", 23, NetworkIntakeQuestion::INPUT_RADIO, $this->yesNoOptions()),
            $this->question('glp1_recent_medication_dosage', 'What medication and dosage are you currently using, or did you use in the last 4 weeks?', 24, NetworkIntakeQuestion::INPUT_LONG_TEXT, helpText: 'Include medication name, dose, concentration, frequency, and fill date if known.', isConditional: true, conditionRules: [['source' => 'answers.glp1_recent_use', 'operator' => 'equals', 'value' => 'yes']]),
            $this->question('glp1_last_dose_date', 'What is the date of your last dose?', 25, NetworkIntakeQuestion::INPUT_DATE, isConditional: true, conditionRules: [['source' => 'answers.glp1_recent_use', 'operator' => 'equals', 'value' => 'yes']]),
            $this->question('glp1_taken_as_prescribed', 'Have you been taking this medication as prescribed?', 26, NetworkIntakeQuestion::INPUT_RADIO, $this->yesNoOptions(), isConditional: true, conditionRules: [['source' => 'answers.glp1_recent_use', 'operator' => 'equals', 'value' => 'yes']]),
            $this->question('glp1_non_prescribed_use_details', 'How have you been taking your medication?', 27, NetworkIntakeQuestion::INPUT_LONG_TEXT, isConditional: true, conditionRules: [['source' => 'answers.glp1_taken_as_prescribed', 'operator' => 'equals', 'value' => 'no']]),
            $this->question('glp1_dose_preference', 'How would you like to continue your dosing plan for this prescription?', 28, NetworkIntakeQuestion::INPUT_SELECT, $this->dosePreferenceOptions(), isConditional: true, conditionRules: [['source' => 'answers.glp1_recent_use', 'operator' => 'equals', 'value' => 'yes']], metadata: ['system_gap' => 'Single-month versus three-month branching is not currently available in rule context.']),
            $this->question('glp1_medication_consent', 'GLP medication acknowledgment and consent', 29, NetworkIntakeQuestion::INPUT_CHECKBOX, $this->agreeCheckboxOptions()),
            $this->question('glp1_compounding_consent', 'Compounding informed consent', 30, NetworkIntakeQuestion::INPUT_CHECKBOX, $this->consentCheckboxOptions(), helpText: 'Compounded medications are not FDA-approved and have not undergone FDA review for safety, efficacy, or quality.'),
            $this->question('glp1_preventive_screening_acknowledgment', 'Acknowledgment of preventive health screening responsibility', 31, NetworkIntakeQuestion::INPUT_CHECKBOX, $this->agreeCheckboxOptions()),
            $this->question('glp1_patient_signature', 'Write your legal name', 32, NetworkIntakeQuestion::INPUT_TEXT),
            $this->question('glp1_signature_date', 'Signature date', 33, NetworkIntakeQuestion::INPUT_DATE, metadata: [
                'frontend_hidden' => true,
                'auto_fill' => NetworkIntakeQuestion::AUTO_FILL_CURRENT_DATE,
            ]),
        ];

        foreach ($questions as &$question) {
            $question['metadata'] = array_merge($question['metadata'] ?? [], [
                'product_slug' => $productSlug,
                'protocol' => 'glp1_initial',
                'follow_up_disabled' => true,
            ]);
        }

        return $questions;
    }

    private function nadInitialQuestions(): array
    {
        return [
            $this->question('nad_goals', 'What are your main goals for starting NAD+ therapy?', 1, NetworkIntakeQuestion::INPUT_MULTISELECT, $this->options([
                'energy_fatigue' => 'Boost energy or reduce fatigue',
                'mental_clarity' => 'Improve mental clarity or focus',
                'anti_aging' => 'Anti-aging or longevity',
                'detox_repair' => 'Detoxification or cellular repair',
                'mood_motivation' => 'Improve mood or motivation',
                'chronic_illness' => 'Support for chronic illness or neurodegeneration',
                'stress_substance_recovery' => 'Recovery from stress, alcohol, or substance use',
                'athletic_recovery' => 'Athletic recovery or performance',
            ]), metadata: ['protocol' => 'nad_initial']),
            $this->question('nad_current_use', 'Do you currently take any form of NAD+?', 2, NetworkIntakeQuestion::INPUT_RADIO, $this->yesNoOptions(), metadata: ['protocol' => 'nad_initial']),
            $this->question('nad_current_use_details', 'Please list the NAD+ product, route, dose, and schedule.', 3, NetworkIntakeQuestion::INPUT_LONG_TEXT, isConditional: true, conditionRules: [['source' => 'answers.nad_current_use', 'operator' => 'equals', 'value' => 'yes']], metadata: ['protocol' => 'nad_initial']),
            $this->question(
                'nad_condition_screen',
                'Do you have any of the following medical conditions?',
                4,
                NetworkIntakeQuestion::INPUT_MULTISELECT,
                $this->options([
                    'cancer_history' => 'Currently have or ever had cancer',
                    'seizures_head_trauma' => 'History of seizures or head trauma',
                    'thyroid_liver_kidney' => 'Uncontrolled or untreated thyroid, liver, or kidney problems',
                    'uncontrolled_heart_disease' => 'Uncontrolled heart disease, arrhythmia, CHF, hypotension, heart attack, or stroke',
                    'active_infection' => 'Active infection',
                    'uncontrolled_mental_mood' => 'Mental or mood disorders that are not controlled',
                    'inflammatory_disorder' => 'History of inflammatory disorders such as rheumatoid arthritis or lupus',
                    'blood_thinners' => 'Medical condition requiring blood thinners',
                    'benzodiazepines' => 'Taking benzodiazepines',
                    'none' => 'None of the above',
                ]),
                networkValidation: $this->blockOnAnySelectedExceptNone('nad_condition_screen', [
                    'cancer_history',
                    'seizures_head_trauma',
                    'thyroid_liver_kidney',
                    'uncontrolled_heart_disease',
                    'active_infection',
                    'uncontrolled_mental_mood',
                    'inflammatory_disorder',
                    'blood_thinners',
                    'benzodiazepines',
                ], 'nad_contraindication', 'Based on this medical history, NAD+ therapy cannot proceed through this telehealth flow.'),
                metadata: ['protocol' => 'nad_initial']
            ),
            $this->question('nad_pregnancy_status', 'Are you currently pregnant, breastfeeding, bottle-feeding with breast milk, or trying to conceive?', 5, NetworkIntakeQuestion::INPUT_RADIO, $this->yesNoOptions(), isConditional: true, conditionRules: $this->femaleCondition(), networkValidation: $this->blockOnEquals('nad_pregnancy_status', 'yes', 'nad_pregnancy_refer_out', 'NAD+ therapy cannot proceed during pregnancy, breastfeeding, or while trying to conceive.', 'refer_out'), metadata: ['protocol' => 'nad_initial']),
            $this->question('nad_blood_pressure', 'What is your current blood pressure reading within the last 6 weeks?', 6, NetworkIntakeQuestion::INPUT_SELECT, $this->options([
                'less_than_100_60' => 'Less than 100/60',
                '110_120_60_70' => '110-120/60-70',
                '120_130_70_80' => '120-130/70-80',
                '130_140_80_90' => '130-140/80-90',
                '140_150_90_100' => '140-150/90-100',
                'unsure' => 'Unsure',
            ]), networkValidation: $this->blockOnAnyEquals('nad_blood_pressure', ['less_than_100_60', 'unsure'], 'nad_blood_pressure_not_clear', 'A current acceptable blood pressure reading is required before NAD+ therapy can proceed.', 'refer_out'), metadata: ['protocol' => 'nad_initial']),
            $this->question('nad_injectable_preservative_allergy', 'Do you have allergies to preservatives or injectables such as benzyl alcohol or lidocaine?', 7, NetworkIntakeQuestion::INPUT_RADIO, $this->yesNoOptions(), metadata: ['protocol' => 'nad_initial']),
            $this->question('nad_injectable_preservative_allergy_details', 'Please describe your preservative or injectable allergy.', 8, NetworkIntakeQuestion::INPUT_LONG_TEXT, isConditional: true, conditionRules: [['source' => 'answers.nad_injectable_preservative_allergy', 'operator' => 'equals', 'value' => 'yes']], metadata: ['protocol' => 'nad_initial']),
            $this->question('nad_medication_allergies', 'Please list any medication allergies that you have.', 9, NetworkIntakeQuestion::INPUT_LONG_TEXT, metadata: ['protocol' => 'nad_initial']),
            $this->question('nad_current_medications', 'List any current prescription medications, supplements, or vitamins you are taking.', 10, NetworkIntakeQuestion::INPUT_LONG_TEXT, metadata: ['protocol' => 'nad_initial']),
            $this->question('nad_route_preference', 'Which NAD+ form do you prefer?', 11, NetworkIntakeQuestion::INPUT_SELECT, $this->options([
                'injection' => 'Injections',
                'oral' => 'NAD+ Nasal Spray',
                'nasal' => 'NAD+ nasal spray',
                'provider_recommend' => 'Let the provider recommend',
            ]), metadata: ['protocol' => 'nad_initial']),
            $this->question('nad_energy_level', 'How would you rate your baseline energy level from 1 lowest to 10 highest?', 12, NetworkIntakeQuestion::INPUT_NUMBER, metadata: ['protocol' => 'nad_initial']),
            $this->question('nad_regular_symptoms', 'Do you experience any of the following regularly?', 13, NetworkIntakeQuestion::INPUT_MULTISELECT, $this->options([
                'brain_fog' => 'Brain fog',
                'low_motivation' => 'Low motivation',
                'poor_sleep' => 'Poor sleep',
                'muscle_soreness' => 'Muscle soreness or slow recovery',
                'chronic_fatigue' => 'Chronic fatigue',
                'cravings' => 'Cravings for alcohol, sugar, or caffeine',
            ]), metadata: ['protocol' => 'nad_initial']),
            $this->question('nad_has_diabetes', 'Do you have diabetes?', 14, NetworkIntakeQuestion::INPUT_RADIO, $this->yesNoOptions(), metadata: ['protocol' => 'nad_initial']),
            $this->question('nad_diabetes_monitoring_acknowledgment', 'If you are diabetic, do you agree to monitor your blood sugar closely?', 15, NetworkIntakeQuestion::INPUT_RADIO, $this->yesNoOptions(), isConditional: true, conditionRules: [['source' => 'answers.nad_has_diabetes', 'operator' => 'equals', 'value' => 'yes']], networkValidation: $this->blockOnEquals('nad_diabetes_monitoring_acknowledgment', 'no', 'nad_diabetes_monitoring_declined', 'Blood sugar monitoring agreement is required for diabetic patients before NAD+ therapy can proceed.', 'refer_out'), metadata: ['protocol' => 'nad_initial']),
            // $this->question('nad_recent_labs_available', 'Do you have A1c and CMP lab results from within the last 6 weeks?', 16, NetworkIntakeQuestion::INPUT_RADIO, $this->yesNoUnsureOptions(), networkValidation: $this->blockOnAnyEquals('nad_recent_labs_available', ['no', 'not_sure'], 'nad_missing_required_labs', 'A1c and CMP labs from within the last 6 weeks are required before NAD+ therapy can proceed.', 'refer_out'), metadata: ['protocol' => 'nad_initial', 'system_gap' => 'Lab artifact upload/verification is not wired into intake answers yet.']),
        ];
    }

    private function glutathioneMicB12InitialQuestions(string $productSlug): array
    {
        $therapyName = match ($productSlug) {
            'b12-injection' => 'B12 therapy',
            'glutathione' => 'Glutathione therapy',
            default => 'wellness therapy',
        };

        $questions = [
            $this->question('wellness_goals', 'What are the main health concerns or goals you want to address?', 1, NetworkIntakeQuestion::INPUT_MULTISELECT, $this->options([
                'fatigue_low_energy' => 'Fatigue or low energy',
                'brain_fog_focus' => 'Brain fog or poor focus',
                'poor_sleep' => 'Poor sleep',
                'stress_burnout' => 'Stress or burnout',
                'immune_detox' => 'Immune support or detox',
                'anti_aging' => 'Anti-aging or longevity',
                'skin_hair' => 'Skin or hair concerns',
                'chronic_illness_inflammation' => 'Chronic illness or inflammation',
            ]), metadata: ['protocol' => 'glutathione_mic_b12_initial', 'product_slug' => $productSlug]),
            $this->question(
                'wellness_global_condition_screen',
                'Do you currently have or have you ever had any of the following conditions?',
                2,
                NetworkIntakeQuestion::INPUT_MULTISELECT,
                $this->options([
                    'uncontrolled_bp_heart_disease' => 'Uncontrolled high blood pressure above 150/90 or heart disease',
                    'cancer_current_past' => 'Cancer, current or past',
                    'liver_kidney_disease' => 'Uncontrolled or untreated liver or kidney disease',
                    'pregnancy_breastfeeding' => 'Pregnant, planning pregnancy, breastfeeding, or bottle-feeding with breast milk',
                    'none' => 'None of the above',
                ]),
                networkValidation: $this->blockOnAnySelectedExceptNone('wellness_global_condition_screen', [
                    'uncontrolled_bp_heart_disease',
                    'cancer_current_past',
                    'liver_kidney_disease',
                    'pregnancy_breastfeeding',
                ], 'wellness_global_contraindication', "This condition prevents automatic progression for {$therapyName}."),
                metadata: ['protocol' => 'glutathione_mic_b12_initial', 'product_slug' => $productSlug]
            ),
        ];

        if ($productSlug === 'glutathione') {
            $questions[] = $this->question(
                'wellness_glutathione_condition_screen',
                'For Glutathione use, do you currently have or have you ever had any of the following?',
                3,
                NetworkIntakeQuestion::INPUT_MULTISELECT,
                $this->options([
                    'asthma' => 'Asthma',
                    'nitrates' => 'Use of nitroglycerin or nitrates including isosorbide or poppers',
                    'sulfa_allergy' => 'Sulfa allergy',
                    'chronic_active_infection' => 'Chronic or active infections such as Lyme or EBV',
                    'none' => 'None of these apply to me',
                ]),
                networkValidation: $this->blockOnAnySelectedExceptNone('wellness_glutathione_condition_screen', ['asthma', 'nitrates', 'sulfa_allergy', 'chronic_active_infection'], 'glutathione_contraindication', 'One or more answers blocks Glutathione therapy through this flow.', 'provider_review_required'),
                metadata: ['protocol' => 'glutathione_mic_b12_initial', 'product_slug' => $productSlug, 'substance' => 'glutathione']
            );
        }

        if ($productSlug === 'b12-injection') {
            $questions[] = $this->question(
                'wellness_b12_condition_screen',
                'For B12 use, do you have or have you ever had any of the following?',
                3,
                NetworkIntakeQuestion::INPUT_MULTISELECT,
                $this->options([
                    'cobalt_cobalamin_allergy' => 'Allergy to cobalt or cobalamin',
                    'hereditary_optic_neuropathy' => 'Hereditary Optic Neuropathy',
                    'elevated_hematocrit' => 'Elevated hematocrit',
                    'polycythemia_vera' => 'Polycythemia Vera',
                    'none' => 'None of these apply to me',
                ]),
                networkValidation: $this->blockOnAnySelectedExceptNone('wellness_b12_condition_screen', ['cobalt_cobalamin_allergy', 'hereditary_optic_neuropathy', 'elevated_hematocrit', 'polycythemia_vera'], 'b12_contraindication', 'One or more answers blocks B12 therapy through this flow.', 'provider_review_required'),
                metadata: ['protocol' => 'glutathione_mic_b12_initial', 'product_slug' => $productSlug, 'substance' => 'b12']
            );
        }

        return array_merge($questions, [
            $this->question('wellness_current_medications', 'List any medications or supplements you currently take regularly.', 4, NetworkIntakeQuestion::INPUT_LONG_TEXT, metadata: ['protocol' => 'glutathione_mic_b12_initial', 'product_slug' => $productSlug]),
            $this->question('wellness_medication_allergies', 'List any medication allergies that you have.', 5, NetworkIntakeQuestion::INPUT_LONG_TEXT, metadata: ['protocol' => 'glutathione_mic_b12_initial', 'product_slug' => $productSlug]),
            $this->question('wellness_stress_level', 'How would you rate your stress level?', 6, NetworkIntakeQuestion::INPUT_SELECT, $this->options(['low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High', 'extreme' => 'Extreme']), metadata: ['protocol' => 'glutathione_mic_b12_initial', 'product_slug' => $productSlug]),
            $this->question('wellness_sleep_hours', 'How many hours of sleep do you get per night on average?', 7, NetworkIntakeQuestion::INPUT_SELECT, $this->options(['under_5' => 'Less than 5', '5_6' => '5-6', '7_8' => '7-8', 'over_8' => 'More than 8']), metadata: ['protocol' => 'glutathione_mic_b12_initial', 'product_slug' => $productSlug]),
            $this->question('wellness_exercise_frequency', 'How often do you exercise?', 8, NetworkIntakeQuestion::INPUT_SELECT, $this->options(['never' => 'Never', '1_2_week' => '1-2 times per week', '3_4_week' => '3-4 times per week', '5_plus_week' => '5 or more times per week']), metadata: ['protocol' => 'glutathione_mic_b12_initial', 'product_slug' => $productSlug]),
            $this->question('wellness_water_intake', 'How much water do you drink per day?', 9, NetworkIntakeQuestion::INPUT_SELECT, $this->options(['under_32' => 'Less than 32 oz', '32_64' => '32-64 oz', '64_100' => '64-100 oz', '100_plus' => '100+ oz']), metadata: ['protocol' => 'glutathione_mic_b12_initial', 'product_slug' => $productSlug]),
            $this->question('wellness_alcohol_frequency', 'How often do you drink alcohol?', 10, NetworkIntakeQuestion::INPUT_SELECT, $this->options(['never_rarely' => 'Never or rarely', '1_3_week' => '1-3 drinks per week', '4_6_week' => '4-6 drinks per week', 'more_than_7_week' => 'More than 7 drinks per week']), metadata: ['protocol' => 'glutathione_mic_b12_initial', 'product_slug' => $productSlug]),
            $this->question('wellness_provider_notes', 'Is there anything else you would like the provider to know?', 11, NetworkIntakeQuestion::INPUT_LONG_TEXT, isRequired: false, metadata: ['protocol' => 'glutathione_mic_b12_initial', 'product_slug' => $productSlug]),
        ]);
    }

    private function question(
        string $key,
        string $text,
        int $order,
        string $inputType,
        ?array $options = null,
        ?string $helpText = null,
        bool $isRequired = true,
        bool $isConditional = false,
        ?array $conditionRules = null,
        ?array $networkValidation = null,
        ?array $metadata = null
    ): array {
        return [
            'question_key' => $key,
            'question_text' => $text,
            'help_text' => $helpText,
            'sort_order' => $order,
            'input_type' => $inputType,
            'options' => $options,
            'is_required' => $isRequired,
            'is_conditional' => $isConditional,
            'condition_rules' => $conditionRules,
            'network_field_mapping' => $text,
            'network_validation' => $networkValidation,
            'metadata' => $metadata,
        ];
    }

    private function blockOnAnySelectedExceptNone(
        string $questionKey,
        array $blockedValues,
        string $reason,
        string $message,
        string $hardStopType = 'refer_out'
    ): array {
        return [
            'blocking_rules' => collect($blockedValues)
                ->map(fn (string $value): array => $this->blockingRule(
                    "{$questionKey}_{$value}",
                    $reason,
                    $message,
                    [['source' => "answers.{$questionKey}", 'operator' => 'equals', 'value' => $value]],
                    $hardStopType
                ))
                ->values()
                ->all(),
        ];
    }

    private function blockOnEquals(
        string $questionKey,
        string $value,
        string $reason,
        string $message,
        string $hardStopType
    ): array {
        return [
            'blocking_rules' => [
                $this->blockingRule(
                    "{$questionKey}_{$value}",
                    $reason,
                    $message,
                    [['source' => "answers.{$questionKey}", 'operator' => 'equals', 'value' => $value]],
                    $hardStopType
                ),
            ],
        ];
    }

    private function blockOnAnyEquals(
        string $questionKey,
        array $values,
        string $reason,
        string $message,
        string $hardStopType
    ): array {
        return [
            'blocking_rules' => collect($values)
                ->map(fn (string $value): array => $this->blockingRule(
                    "{$questionKey}_{$value}",
                    $reason,
                    $message,
                    [['source' => "answers.{$questionKey}", 'operator' => 'equals', 'value' => $value]],
                    $hardStopType
                ))
                ->values()
                ->all(),
        ];
    }

    private function blockingRule(
        string $ruleKey,
        string $reason,
        string $message,
        array $conditions,
        string $hardStopType
    ): array {
        return [
            'rule_key' => $ruleKey,
            'reason' => $reason,
            'hard_stop_type' => $hardStopType,
            'message' => $message,
            'conditions' => $conditions,
        ];
    }

    private function options(array $options): array
    {
        return collect($options)
            ->map(fn (string $label, string $value): array => [
                'id' => $value,
                'label' => $label,
                'value' => $value,
            ])
            ->values()
            ->all();
    }

    private function yesNoOptions(): array
    {
        return $this->options([
            'yes' => 'Yes',
            'no' => 'No',
        ]);
    }

    private function yesNoUnsureOptions(): array
    {
        return $this->options([
            'yes' => 'Yes',
            'no' => 'No',
            'not_sure' => 'Not sure',
        ]);
    }

    private function acknowledgmentCheckboxOptions(): array
    {
        return $this->options([
            'acknowledged' => 'I acknowledge that I have read and understand this information',
        ]);
    }

    private function agreeCheckboxOptions(): array
    {
        return $this->options([
            'agree' => 'I acknowledge and agree',
        ]);
    }

    private function consentCheckboxOptions(): array
    {
        return $this->options([
            'consent' => 'I acknowledge and consent',
        ]);
    }

    private function contraceptionOptions(): array
    {
        return $this->options([
            'none' => 'Not using any contraception',
            'oral_pills' => 'Oral birth control pills',
            'injectable_implant' => 'Injectables or implants',
            'patch_ring' => 'Patches or vaginal ring',
            'iud' => 'Intrauterine device (IUD)',
            'barrier' => 'Barrier method',
            'partner_vasectomy' => 'Partner vasectomy',
            'sterilization' => 'Tubal ligation or sterilization',
            'not_applicable' => 'Not applicable',
        ]);
    }

    private function dosePreferenceOptions(): array
    {
        return $this->options([
            'decrease' => 'Decrease dose',
            'maintain' => 'Maintain dose',
            'increase' => 'Increase dose',
        ]);
    }

    private function glp1ProductName(string $productSlug): string
    {
        return match ($productSlug) {
            'tirzepatide' => 'Tirzepatide',
            default => 'Semaglutide',
        };
    }

    private function femaleCondition(): array
    {
        return [
            ['source' => 'patient.gender', 'operator' => 'equals', 'value' => 'female'],
        ];
    }

    private function flowAlias(string $flowKey): string
    {
        return match ($flowKey) {
            OlaHealthNetworkSeeder::VIDEO_FLOW_KEY => 'video',
            default => 'async',
        };
    }
}
