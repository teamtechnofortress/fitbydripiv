<?php

namespace App\Support\Content\Sections\Handlers;

use App\Models\PageSection;

class SpacerSection
{
    public static function handle(PageSection $section): array
    {
        $content = is_array($section->content) ? $section->content : [];

        return [
            'id' => $section->id,
            'section_key' => $section->section_key,
            'type' => $section->type?->value ?? $section->getRawOriginal('type'),
            'height' => $content['height'] ?? 32,
            'desktop' => $content['desktop'] ?? null,
            'tablet' => $content['tablet'] ?? null,
            'mobile' => $content['mobile'] ?? null,
            'sort_order' => $section->sort_order,
        ];
    }
}
