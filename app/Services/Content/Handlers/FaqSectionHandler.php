<?php

namespace App\Services\Content\Handlers;

use App\Models\PageSection;

class FaqSectionHandler
{
    public static function handle(PageSection $section, array $data): void
    {
        $section->faqs()->delete();

        foreach (array_values($data['faqs'] ?? []) as $index => $faq) {
            $section->faqs()->create([
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'sort_order' => $faq['sort_order'] ?? ($index + 1),
                'is_active' => $faq['is_active'] ?? true,
            ]);
        }
    }
}
