<?php

namespace App\Support\Content\Sections\Handlers;

use App\Models\PageSection;

class SectionHeaderSection
{
    public static function handle(PageSection $section): array
    {
        $content = is_array($section->content) ? $section->content : [];

        return [
            'id' => $section->id,
            'section_key' => $section->section_key,
            'type' => $section->type?->value ?? $section->getRawOriginal('type'),
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'headline' => $content['headline'] ?? null,
            'description' => $content['description'] ?? null,
            'alignment' => $content['alignment'] ?? 'left',
            'sort_order' => $section->sort_order,
        ];
    }
}
