<?php

namespace App\Support\Content\Sections\Handlers;

use App\Models\PageSection;

class RichTextSection
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
            'html' => (string) ($content['html'] ?? ''),
            'source' => $content['source'] ?? null,
            'source_filename' => $content['source_filename'] ?? null,
            'alignment' => $content['alignment'] ?? 'left',
            'max_width' => $content['max_width'] ?? 'content',
            'background_style' => $content['background_style'] ?? null,
            'content' => $content,
            'sort_order' => $section->sort_order,
        ];
    }
}
