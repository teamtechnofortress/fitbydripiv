<?php

namespace App\Services\Content;

use App\Models\PageSection;
use App\Services\Content\Handlers\SectionHandlerFactory;
use Illuminate\Support\Facades\DB;

class SectionService
{
    public function store(array $data): PageSection
    {
        return DB::transaction(function () use ($data) {
            $section = isset($data['id'])
                ? PageSection::findOrFail($data['id'])
                : new PageSection();

            $section->fill($data);
            $section->type = $data['type'] ?? 'default';
            $section->sort_order = $data['sort_order'] ?? $section->sort_order ?? 0;
            $section->save();

            SectionHandlerFactory::handle($section, $data);

            return $section->load(['items', 'faqs']);
        });
    }
}
