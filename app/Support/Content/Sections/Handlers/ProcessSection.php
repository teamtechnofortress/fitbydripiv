<?php

namespace App\Support\Content\Sections\Handlers;

use App\Models\PageSection;

class ProcessSection
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
            'variant' => $content['variant'] ?? 'default',
            'background_style' => $content['background_style'] ?? null,
            'items' => $section->items->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'icon' => $item->icon,
                'image' => $item->image,
                'sort_order' => $item->sort_order,
            ])->values()->all(),
            'sort_order' => $section->sort_order,
        ];
    }
}
