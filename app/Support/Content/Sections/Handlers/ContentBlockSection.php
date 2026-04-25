<?php

namespace App\Support\Content\Sections\Handlers;

use App\Models\PageSection;

class ContentBlockSection
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
            'intro' => $content['intro'] ?? null,
            'paragraphs' => array_values($content['paragraphs'] ?? []),
            'bullets' => array_values($content['bullets'] ?? []),
            'rows' => array_values($content['rows'] ?? []),
            'alignment' => $content['alignment'] ?? 'left',
            'max_width' => $content['max_width'] ?? 'content',
            'background_style' => $content['background_style'] ?? null,
            'content' => $content,
            'sort_order' => $section->sort_order,
        ];
    }
}
