<?php

namespace Database\Seeders;

use App\Enums\SectionType;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

class TelehealthFaqPageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'telehealth-faq'],
            [
                'title' => 'Telehealth FAQ',
                'status' => 'published',
                'meta_title' => 'FitByShot | Telehealth FAQ',
                'meta_description' => 'Everything you need to know about FitByShot telehealth consultations, intake, approval, follow-up, and patient support.',
            ]
        );

        $this->upsertSection($page, [
            'section_key' => 'telehealth_faq_header',
            'type' => SectionType::SECTION_HEADER->value,
            'title' => 'Telehealth FAQ Header',
            'subtitle' => 'Top banner for telehealth FAQ page',
            'content' => [
                'headline' => 'Personalized Telehealth Consultations',
                'description' => 'Everything you need to know about our remote healthcare services',
                'alignment' => 'center',
                'background_style' => 'soft_gradient',
                'spacing' => 'comfortable',
            ],
            'sort_order' => 1,
        ]);

        $faqSection = $this->upsertSection($page, [
            'section_key' => 'telehealth_faqs',
            'type' => SectionType::FAQ->value,
            'title' => 'Telehealth Frequently Asked Questions',
            'subtitle' => 'Common telehealth questions answered',
            'content' => [
                'variant' => 'accordion',
                'intro_headline' => 'How Telehealth Works at FitByShot',
                'intro_body' => [
                    'Our telehealth process is designed to be simple, secure, and effective. Complete our comprehensive intake form about your health and goals, and our licensed medical professionals will review your information to determine your eligibility for treatment. Once approved, your customized prescription is delivered directly to your door.',
                    'All treatments are administered under medical supervision with ongoing support throughout your wellness journey.',
                ],
                'card_style' => 'outlined',
            ],
            'sort_order' => 2,
        ]);

        $this->syncFaqs($faqSection, [
            [
                'question' => 'What is telehealth and how does it work?',
                'answer' => 'Telehealth allows you to complete your health intake remotely. Our medical team reviews your submitted information, determines eligibility, and guides your treatment plan without requiring an in-person visit for the standard workflow.',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'question' => 'How long does the telehealth review process take?',
                'answer' => 'Most telehealth reviews are completed within 24 to 48 hours after you submit your intake form, although timing can vary if additional information is needed.',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'question' => 'What information do I need to provide?',
                'answer' => 'You should be prepared to provide your medical history, current medications, treatment goals, and any relevant health details that help our providers evaluate your eligibility safely.',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'question' => 'Who will review my medical information?',
                'answer' => 'Licensed medical professionals on our care team review your submission and determine whether treatment is appropriate based on your health profile and goals.',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'question' => 'Is my health information secure?',
                'answer' => 'Yes. Your health information is handled through secure systems and reviewed only by authorized medical personnel involved in your care and support.',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'question' => 'What happens if I am not approved?',
                'answer' => 'If you are not approved for treatment, our team will notify you and may provide guidance on next steps or recommend discussing alternative care options with an appropriate provider.',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'question' => 'Can I speak directly with a medical provider?',
                'answer' => 'If your case requires additional clarification or follow-up, our team will advise you on the appropriate next step and whether direct provider communication is needed.',
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'question' => 'What if my health status changes?',
                'answer' => 'You should update our team promptly if your health status, medications, or symptoms change so your treatment plan can be reviewed and adjusted appropriately.',
                'sort_order' => 8,
                'is_active' => true,
            ],
            [
                'question' => 'How often will I need follow-up consultations?',
                'answer' => 'Follow-up frequency depends on your treatment plan, response to care, and provider guidance. Some patients require periodic check-ins to monitor progress and safety.',
                'sort_order' => 9,
                'is_active' => true,
            ],
            [
                'question' => 'Can I use telehealth if I live in any state?',
                'answer' => 'Availability depends on provider coverage, regulatory requirements, and where services can legally be offered. Eligibility is confirmed during the intake and review process.',
                'sort_order' => 10,
                'is_active' => true,
            ],
        ]);

        $this->upsertSection($page, [
            'section_key' => 'telehealth_faq_cta',
            'type' => SectionType::SECTION_HEADER->value,
            'title' => 'Telehealth FAQ Closing Header',
            'subtitle' => 'Closing support block for telehealth FAQ page',
            'content' => [
                'headline' => 'Ready to Get Started?',
                'description' => "Take the first step toward your health and wellness goals. Complete our telehealth intake form, and our medical team will review your information within 0-48 hours to determine your eligibility for treatment.\n\nIf you have additional questions about the telehealth process, please do not hesitate to contact our support team.",
                'alignment' => 'left',
                'background_style' => 'soft_mint_card',
            ],
            'sort_order' => 3,
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
