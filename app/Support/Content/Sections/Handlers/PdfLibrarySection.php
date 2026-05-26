<?php

namespace App\Support\Content\Sections\Handlers;

use App\Models\PageSection;

class PdfLibrarySection
{
    public static function handle(PageSection $section): array
    {
        $content = is_array($section->content) ? $section->content : [];
        $settings = is_array($content['settings'] ?? null) ? $content['settings'] : [];
        $documents = array_values($content['documents'] ?? []);
        $documents = array_map(function ($document) {
            if (! is_array($document)) {
                return [];
            }

            $viewUrl = $document['view_url'] ?? $document['pdf_url'] ?? null;
            $downloadUrl = $document['download_url'] ?? $document['pdf_url'] ?? null;
            $button = is_array($document['item'] ?? null) ? $document['item'] : [];

            return [
                ...$document,
                'view_url' => $viewUrl,
                'download_url' => $downloadUrl,
                'download_label' => $document['download_label'] ?? 'Download PDF',
                'is_available' => filled($viewUrl) || filled($downloadUrl),
                'can_view' => filled($viewUrl),
                'can_download' => filled($downloadUrl),
                'item' => [
                    'type' => $button['type'] ?? 'button',
                    'label' => $button['label'] ?? 'Download PDF',
                    'style' => $button['style'] ?? 'primary',
                    'action' => $button['action'] ?? 'download',
                    'download_url' => $button['download_url'] ?? $downloadUrl,
                ],
            ];
        }, $documents);

        return [
            'id' => $section->id,
            'section_key' => $section->section_key,
            'type' => $section->type?->value ?? $section->getRawOriginal('type'),
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'headline' => $content['headline'] ?? null,
            'description' => $content['description'] ?? null,
            'upload_help' => $content['upload_help'] ?? null,
            'settings' => $settings,
            'viewer_enabled' => $settings['viewer_enabled'] ?? ($content['viewer_enabled'] ?? true),
            'viewer_embed_mode' => $settings['viewer_embed_mode'] ?? ($content['viewer_embed_mode'] ?? 'iframe'),
            'documents' => $documents,
            'items' => $section->items->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'icon' => $item->icon,
                'image' => $item->image,
                'sort_order' => $item->sort_order,
            ])->values()->all(),
            'content' => $content,
            'sort_order' => $section->sort_order,
        ];
    }
}
