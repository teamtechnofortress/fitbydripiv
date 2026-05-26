<?php

namespace Database\Seeders;

use App\enums\SectionType;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

class PenInstructionsPageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'pen-instructions'],
            [
                'title' => 'Pen Injection Instructions',
                'status' => 'published',
                'meta_title' => 'FitByShot | Pen Injection Instructions',
                'meta_description' => 'Pen injection instruction PDF viewer with download support.',
            ]
        );

        $library = $this->upsertSection($page, [
            'section_key' => 'pdf_library',
            'type' => SectionType::PDF_LIBRARY->value,
            'title' => 'PDF Viewer',
            'subtitle' => 'PDF view and download',
            'content' => [
                'headline' => 'Pen Instructions',
                'description' => 'View a PDF in-page and download it using the document button.',
                'upload_help' => '',
                'download_help' => 'Use `download_url` for direct file download. Falls back to `pdf_url` if needed.',
                'settings' => [
                    'viewer_enabled' => true,
                    'viewer_embed_mode' => 'iframe',
                ],
                'accepted_file_types' => ['application/pdf'],
                'documents' => [
                    [
                        'key' => 'general_pdf_document',
                        'label' => 'General PDF Document',
                        'view_url' => null,
                        'pdf_url' => null,
                        'download_url' => null,
                        'download_label' => 'Download PDF',
                        'item' => [
                            'type' => 'button',
                            'label' => 'Download PDF',
                            'style' => 'primary',
                            'action' => 'download',
                            'download_url' => null,
                        ],
                        'version' => null,
                        'last_updated' => null,
                    ],
                ],
            ],
            'sort_order' => 2,
        ]);
        $this->clearItems($library);

        $this->cleanupRemovedSections($page, [
            'pen_instructions_header',
            'pen_instructions_pdf_library',
            'pdf_library',
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

    protected function clearItems(PageSection $section): void
    {
        $section->items()->delete();
    }

    protected function cleanupRemovedSections(Page $page, array $allowedSectionKeys): void
    {
        PageSection::query()
            ->where('page_id', $page->id)
            ->whereNotIn('section_key', $allowedSectionKeys)
            ->delete();
    }
}
