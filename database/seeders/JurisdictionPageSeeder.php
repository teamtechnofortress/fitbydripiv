<?php

namespace Database\Seeders;

use App\enums\SectionType;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

class JurisdictionPageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'jurisdiction'],
            [
                'title' => 'Jurisdictions Served',
                'status' => 'published',
                'meta_title' => 'FitByShot | Jurisdictions Served',
                'meta_description' => 'Review the United States jurisdictions where FitByShot services are currently available.',
            ]
        );

        $this->upsertSection($page, [
            'section_key' => 'jurisdiction_header',
            'type' => SectionType::SECTION_HEADER->value,
            'title' => 'Jurisdiction Header',
            'subtitle' => 'Top banner for jurisdiction page',
            'content' => [
                'headline' => 'Jurisdictions Served',
                'description' => 'Service availability for FitByShot telehealth consultations, prescriptions, and related wellness programs',
                'alignment' => 'center',
                'spacing' => 'comfortable',
            ],
            'sort_order' => 1,
        ]);


        $this->upsertSection($page, [
            'section_key' => 'jurisdiction_overview',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Jurisdictions Served',
            'subtitle' => '',
            'content' => [
                'headline' => 'Jurisdictions Served',
                'paragraphs' => [
                    'FitByShot.com connects telehealth consultations, prescriptions, and related services for GLP-1 and GLP-2 weight loss medications (such as compounded semaglutide and compounded tirzepatide or branded equivalents where applicable), other health and wellness compounded peptides, and associated wellness programs.',
                    'We currently offer services to patients physically located in all 50 United States.',
                ],
                'alignment' => 'left',
                'max_width' => 'wide',
            ],
            'sort_order' => 3,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'service_states',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Service States',
            'subtitle' => '',
            'content' => [
                'headline' => 'Available in these states',
                'intro' => 'This includes:',
                'paragraphs' => [],
                'bullets' => [],
                'grid_bullets' => [
                    'Alabama (AL)',
                    'Alaska (AK)',
                    'Arizona (AZ)',
                    'Arkansas (AR)',
                    'California (CA)',
                    'Colorado (CO)',
                    'Connecticut (CT)',
                    'Delaware (DE)',
                    'Florida (FL)',
                    'Georgia (GA)',
                    'Hawaii (HI)',
                    'Idaho (ID)',
                    'Illinois (IL)',
                    'Indiana (IN)',
                    'Iowa (IA)',
                    'Kansas (KS)',
                    'Kentucky (KY)',
                    'Louisiana (LA)',
                    'Maine (ME)',
                    'Maryland (MD)',
                    'Massachusetts (MA)',
                    'Michigan (MI)',
                    'Minnesota (MN)',
                    'Mississippi (MS)',
                    'Missouri (MO)',
                    'Montana (MT)',
                    'Nebraska (NE)',
                    'Nevada (NV)',
                    'New Hampshire (NH)',
                    'New Jersey (NJ)',
                    'New Mexico (NM)',
                    'New York (NY)',
                    'North Carolina (NC)',
                    'North Dakota (ND)',
                    'Ohio (OH)',
                    'Oklahoma (OK)',
                    'Oregon (OR)',
                    'Pennsylvania (PA)',
                    'Rhode Island (RI)',
                    'South Carolina (SC)',
                    'South Dakota (SD)',
                    'Tennessee (TN)',
                    'Texas (TX)',
                    'Utah (UT)',
                    'Vermont (VT)',
                    'Virginia (VA)',
                    'Washington (WA)',
                    'West Virginia (WV)',
                    'Wisconsin (WI)',
                    'Wyoming (WY)',
                ],
                'rows' => [],
                'alignment' => 'left',
                'max_width' => 'wide',
                'background_style' => null,
            ],
            'sort_order' => 4,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'jurisdiction_important_notes',
            'type' => SectionType::CONTENT_BLOCK->value,
            'title' => 'Important Notes',
            'subtitle' => '',
            'content' => [
                'headline' => 'Important Notes',
                'bullets' => [
                    'Services are available only to patients who are physically located in one of the jurisdictions listed above at the time of the telehealth consultation. We do not currently serve U.S. territories (e.g., Puerto Rico, Guam, U.S. Virgin Islands) or international patients unless otherwise specified and compliant with applicable laws.',
                    'Availability of specific medications (including GLP-1/GLP-2 agonists and peptides), treatment protocols, and partner pharmacy fulfillment is subject to state-specific regulations, provider licensure, Rite-Away pharmacy licensing, and clinical guidelines. Some services or formulations may not be available in every state due to varying state laws on telemedicine, prescribing, compounding, or controlled substances.',
                    'All prescriptions are issued by our contracted licensed healthcare providers and prescriptions provided through our contracted Dr Network (OLA Health) of independent doctors licensed in the jurisdiction in which the patient resides in compliance with the laws of both the provider’s jurisdiction and the patient’s jurisdiction. FitByShot.com does not prescribe directly nor distribute directly any medications in violation of any federal, state, or local laws. All prescriptions and medications are prescribed and distributed by only by FitByShot.com licensed Healthcare contracted companies deemed by Dr Network (Ola Health) to be in compliance with all local and federal laws and regulatory laws and regulations.',
                    'This list is subject to change. We continuously monitor and update our service areas based on Ola Health information and licensure, regulatory requirements, and compliance obligations. Patients will be notified during the intake process if services are unavailable in their specific location.',
                ],
                'alignment' => 'left',
                'max_width' => 'wide',
            ],
            'sort_order' => 5,
        ]);
    }

    protected function upsertSection(Page $page, array $attributes): PageSection
    {
        return PageSection::updateOrCreate(
            [
                'page_id' => $page->id,
                'section_key' => $attributes['section_key'],
            ],
            [
                'type' => $attributes['type'],
                'title' => $attributes['title'] ?? null,
                'subtitle' => $attributes['subtitle'] ?? null,
                'content' => $attributes['content'] ?? null,
                'image' => $attributes['image'] ?? null,
                'sort_order' => $attributes['sort_order'] ?? 0,
            ]
        );
    }
}
