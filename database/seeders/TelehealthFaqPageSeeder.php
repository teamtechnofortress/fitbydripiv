<?php

namespace Database\Seeders;

use App\enums\SectionType;
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
                'answer' => 'Telehealth allows you to consult with licensed medical professionals remotely through our secure online platform. You complete a comprehensive intake form about your health and goals, and our medical team reviews your information to determine eligibility for treatment. This convenient approach eliminates the need for in-person office visits while maintaining high standards of medical care.',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'question' => 'How long does the telehealth review process take?',
                'answer' => 'Our medical professionals typically review your intake information within 0-48 hours. You will be notified once your review is complete and whether you have been approved for treatment. In some cases, our team may request additional information to ensure your safety and treatment effectiveness.',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'question' => 'What information do I need to provide?',
                'answer' => 'You will need to provide comprehensive information about your current health status, medical history, current medications, allergies, treatment goals, and any relevant health conditions. Be thorough and honest in your responses to ensure our medical team can make the best determination for your care.',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'question' => 'Who will review my medical information?',
                'answer' => 'Your information is reviewed by licensed medical professionals who specialize in the treatment areas we offer. All of our providers are qualified to prescribe medications and make medical determinations based on your health profile and treatment goals.',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'question' => 'Is my health information secure?',
                'answer' => 'Yes, we take your privacy seriously. All of your personal health information is protected in compliance with HIPAA regulations. We use secure, encrypted systems to store and transmit your data, and only authorized medical professionals have access to your information.',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'question' => 'What happens if I am not approved?',
                'answer' => 'If our medical team determines that a particular treatment is not appropriate for you based on your health profile, we will notify you of the decision and, when possible, suggest alternative options that may be more suitable for your goals and circumstances.',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'question' => 'Can I speak directly with a medical provider?',
                'answer' => 'While our primary consultation process is through our comprehensive intake form and medical review, you can reach out to our medical team with questions or concerns at any time. For complex situations, direct consultations can be arranged.',
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'question' => 'What if my health status changes?',
                'answer' => 'You should inform our medical team immediately if your health status changes, if you start new medications, or if you experience any unexpected symptoms. All subsequent orders are reviewed in the context of your initial health profile and any updates you provide.',
                'sort_order' => 8,
                'is_active' => true,
            ],
            [
                'question' => 'How often will I need follow-up consultations?',
                'answer' => 'Follow-up frequency depends on your specific treatment and individual needs. Some treatments require regular check-ins to monitor progress and adjust dosages, while others may need less frequent oversight. Our medical team will provide guidance specific to your treatment plan.',
                'sort_order' => 9,
                'is_active' => true,
            ],
            [
                'question' => 'Can I use telehealth if I live in any state?',
                'answer' => 'Telehealth regulations vary by state. We are continuously expanding our service areas. During the intake process, we will verify that we can serve patients in your location. If we cannot currently serve your state, we will let you know.',
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
                'description' => "Take the first step toward your health and wellness goals. Complete our telehealth intake form, and our medical team will review your information within 0-48 hours to determine your eligibility for treatment.\n\nIf you have additional questions about the telehealth process, please don't hesitate to contact our support team.",
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
