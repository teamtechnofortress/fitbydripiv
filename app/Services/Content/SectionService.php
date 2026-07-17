<?php

namespace App\Services\Content;

use App\enums\SectionType;
use App\Models\PageSection;
use App\Services\Content\Handlers\SectionHandlerFactory;
use Illuminate\Support\Facades\DB;

class SectionService
{
    public function __construct(
        private readonly HtmlSanitizer $htmlSanitizer,
    ) {}

    public function store(array $data): PageSection
    {
        return DB::transaction(function () use ($data) {
            $section = isset($data['id'])
                ? PageSection::findOrFail($data['id'])
                : new PageSection();

            $data = $this->normalizeSectionData($data);

            $section->fill($data);
            $section->type = $data['type'] ?? 'default';
            $section->sort_order = $data['sort_order'] ?? $section->sort_order ?? 0;
            $section->save();

            SectionHandlerFactory::handle($section, $data);

            return $section->load(['items', 'faqs']);
        });
    }

    private function normalizeSectionData(array $data): array
    {
        $type = SectionType::tryFrom((string) ($data['type'] ?? SectionType::DEFAULT->value));

        if ($type !== SectionType::RICH_TEXT) {
            return $data;
        }

        $content = is_array($data['content'] ?? null) ? $data['content'] : [];

        $data['content'] = [
            'html' => $this->htmlSanitizer->sanitize((string) ($content['html'] ?? '')),
            'source' => $content['source'] ?? 'manual',
            'source_filename' => $content['source_filename'] ?? null,
            'alignment' => $content['alignment'] ?? 'left',
            'max_width' => $content['max_width'] ?? 'wide',
            'background_style' => $content['background_style'] ?? null,
            'warnings' => array_values($content['warnings'] ?? []),
        ];

        return $data;
    }
}
