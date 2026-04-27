<?php

namespace Database\Seeders;

use App\enums\SectionType;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

class GeneralFaqPageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'faq'],
            [
                'title' => 'Frequently Asked Questions',
                'status' => 'published',
                'meta_title' => 'FitByShot | Frequently Asked Questions',
                'meta_description' => 'Answers to common questions about FitByShot treatments, prescriptions, delivery, safety, and getting started.',
            ]
        );

        $this->upsertSection($page, [
            'section_key' => 'general_faq_header',
            'type' => SectionType::SECTION_HEADER->value,
            'title' => 'General FAQ Header',
            'subtitle' => 'Top banner for FAQ page',
            'content' => [
                'headline' => 'Frequently Asked Questions',
                'description' => 'Find answers to common questions about our treatments and services',
                'alignment' => 'center',
                'background_style' => 'soft_gradient',
                'spacing' => 'comfortable',
            ],
            'sort_order' => 1,
        ]);

        $this->upsertSection($page, [
            'section_key' => 'general_faq_top_spacer',
            'type' => SectionType::SPACER->value,
            'title' => 'General FAQ Spacer',
            'subtitle' => 'Spacing between hero header and faq accordion',
            'content' => [
                'height' => 60,
                'desktop' => 60,
                'tablet' => 60,
                'mobile' => 40,
            ],
            'sort_order' => 2,
        ]);

        $faqSection = $this->upsertSection($page, [
            'section_key' => 'general_faqs',
            'type' => SectionType::FAQ->value,
            'title' => 'General Frequently Asked Questions',
            'subtitle' => 'Common FitByShot questions answered',
            'content' => [
                'variant' => 'accordion',
                'card_style' => 'outlined',
                'allow_first_open' => true,
            ],
            'sort_order' => 3,
        ]);

        $this->syncFaqs($faqSection, [
            [
                'question' => 'How do I get started with FitByShot?',
                'answer' => 'Getting started is simple! Complete our Telehealth form about your health and goals. Our medical team will review your information to determine your eligibility for treatment. Once approved, your customized prescription will be delivered right to your door.',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'question' => 'What treatments do you offer?',
                'answer' => 'We offer three main categories of treatments: Weight Loss (Semaglutide and Tirzepatide for prescription weight loss), Wellness (B12 for energy and metabolism, Glutathione for immunity and detoxification), and Longevity (NAD+ therapy for cellular energy and healthy aging).',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'question' => 'How long until I see results?',
                'answer' => 'Results vary by treatment. Weight loss treatments typically show results within 2-4 weeks. NAD+ therapy may provide immediate energy benefits, with long-term cellular benefits over 4-8 weeks. B12 energy improvements are often noticed within 24-48 hours. Glutathione benefits may be noticed within 2-4 weeks.',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'question' => 'Are these treatments safe?',
                'answer' => 'Yes, all our treatments are administered under medical supervision. Our medical professionals review your health information and eligibility before prescribing any treatment. We provide ongoing support and monitoring throughout your wellness journey.',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'question' => 'How are the treatments delivered?',
                'answer' => 'Your customized prescription is delivered reliably right to your door. Delivery methods vary by treatment: weight loss medications are weekly injections, NAD+ is available as IV, nasal, oral, or injectable forms, B12 is administered via intramuscular injection, and Glutathione is available in multiple forms including IV, injection, or oral.',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'question' => 'What if I need to adjust my treatment?',
                'answer' => 'Any subsequent orders will be reviewed in the context of your initial health and wellness goals. Our medical team monitors your progress and can adjust your treatment plan as needed to ensure optimal results. Contact us anytime if you have concerns or need modifications.',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'question' => 'Do I need a prescription for these treatments?',
                'answer' => 'Yes, all our treatments require a prescription. After you complete our Telehealth form, our medical professionals will review your eligibility and prescribe the appropriate treatment based on your health goals and medical history.',
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'question' => 'What makes FitByShot different?',
                'answer' => 'FitByShot offers prescription weight loss, longevity, and wellness specific to your goals. We provide support for body, mind, and cellular health with treatments customized for your needs. Health is simplified and reliably delivered right to your door with ongoing medical oversight.',
                'sort_order' => 8,
                'is_active' => true,
            ],
        ]);

        $this->upsertSection($page, [
            'section_key' => 'general_faq_contact_cta',
            'type' => SectionType::TELEHEALTH_CTA->value,
            'title' => 'General FAQ Contact CTA',
            'subtitle' => 'Closing contact CTA block for FAQ page',
            'content' => [
                'headline' => 'Still have questions?',
                'description' => "Our team is here to help. Get in touch and we'll answer any questions you have.",
                'button' => [
                    'label' => 'Contact Us',
                    'link' => '/contact',
                    'style' => 'primary',
                ],
                'layout' => 'centered_cta',
            ],
            'sort_order' => 4,
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

    protected function syncFaqs(PageSection $section, array $faqs): void
    {
        $section->faqs()->delete();

        foreach ($faqs as $faq) {
            $section->faqs()->create([
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'sort_order' => $faq['sort_order'] ?? 0,
                'is_active' => $faq['is_active'] ?? true,
            ]);
        }
    }
}
