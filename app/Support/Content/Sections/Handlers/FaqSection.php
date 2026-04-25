<?php

namespace App\Support\Content\Sections\Handlers;

use App\Models\PageSection;

class FaqSection
{
    public static function handle(PageSection $section): array
    {
        return [
            'id' => $section->id,
            'section_key' => $section->section_key,
            'type' => $section->type?->value ?? $section->getRawOriginal('type'),
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'content' => $section->content,
            'image' => $section->image,
            'sort_order' => $section->sort_order,
            'faqs' => $section->faqs->map(fn ($faq) => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'sort_order' => $faq->sort_order,
                'is_active' => $faq->is_active,
            ])->values()->all(),
        ];
    }
}
