<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documentTypes = [
            [
                'key' => 'passport',
                'name' => 'Passport',
                'category' => DocumentType::CATEGORY_IDENTITY,
                'description' => 'Government-issued passport document.',
                'metadata' => [
                    'requires_expiry_date' => true,
                    'requires_back_side' => false,
                ],
            ],
            [
                'key' => 'driver_license',
                'name' => 'Driver License',
                'category' => DocumentType::CATEGORY_IDENTITY,
                'description' => 'Government-issued driver license.',
                'metadata' => [
                    'requires_expiry_date' => true,
                    'requires_back_side' => true,
                ],
            ],
            [
                'key' => 'selfie',
                'name' => 'Selfie',
                'category' => DocumentType::CATEGORY_VERIFICATION,
                'description' => 'Patient selfie for identity verification.',
                'metadata' => [
                    'requires_expiry_date' => false,
                    'requires_back_side' => false,
                ],
            ],
            [
                'key' => 'insurance_card',
                'name' => 'Insurance Card',
                'category' => DocumentType::CATEGORY_INSURANCE,
                'description' => 'Patient insurance card.',
                'metadata' => [
                    'requires_expiry_date' => false,
                    'requires_back_side' => true,
                ],
            ],
            [
                'key' => 'lab_report',
                'name' => 'Lab Report',
                'category' => DocumentType::CATEGORY_MEDICAL,
                'description' => 'Medical laboratory report.',
                'metadata' => [
                    'requires_expiry_date' => false,
                    'requires_back_side' => false,
                ],
            ],
            [
                'key' => 'consent_form',
                'name' => 'Consent Form',
                'category' => DocumentType::CATEGORY_CONSENT,
                'description' => 'Signed patient consent form.',
                'metadata' => [
                    'requires_signature' => true,
                ],
            ],
            [
                'key' => 'prescription',
                'name' => 'Prescription',
                'category' => DocumentType::CATEGORY_PRESCRIPTION,
                'description' => 'Prescription document.',
                'metadata' => [
                    'requires_prescriber' => true,
                ],
            ],
        ];

        foreach ($documentTypes as $documentType) {
            DocumentType::query()->updateOrCreate(
                ['key' => $documentType['key']],
                array_merge($documentType, ['is_active' => true])
            );
        }
    }
}
