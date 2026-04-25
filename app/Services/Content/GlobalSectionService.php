<?php

namespace App\Services\Content;

use App\Models\CmsResearchLink;
use App\Models\GlobalSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GlobalSectionService
{
    public function all(): Collection
    {
        $this->ensureDefaults();

        return GlobalSection::query()
            ->orderBy('key')
            ->get();
    }

    public function save(array $data): GlobalSection
    {
        return DB::transaction(function () use ($data) {
            $this->ensureDefaults();

            $definition = $this->definition($data['key']);

            if ($definition === null) {
                throw ValidationException::withMessages([
                    'key' => 'Unknown global section key.',
                ]);
            }

            $section = isset($data['id'])
                ? GlobalSection::findOrFail($data['id'])
                : GlobalSection::firstOrNew(['key' => $data['key']]);

            $config = $data['config'] ?? ($section->config ?? $definition['config'] ?? []);

            $section->fill([
                'key' => $data['key'],
                'type' => $definition['type'],
                'config' => $config,
            ]);
            $section->save();

            if ($data['key'] === 'footer') {
                $this->replaceGlobalResearchLinks($config);
            }

            return $section->fresh();
        });
    }

    public function ensureDefaults(): void
    {
        foreach ($this->definitions() as $key => $definition) {
            $section = GlobalSection::firstOrNew(['key' => $key]);

            if (! $section->exists) {
                $section->fill([
                    'type' => $definition['type'] ?? $key,
                    'config' => $definition['config'] ?? [],
                ]);
                $section->save();

                continue;
            }

            if (blank($section->type)) {
                $section->type = $definition['type'] ?? $key;
                $section->save();
            }
        }
    }

    public function allowedKeys(): array
    {
        return array_keys($this->definitions());
    }

    protected function definitions(): array
    {
        return config('global_sections', []);
    }

    protected function definition(string $key): ?array
    {
        return $this->definitions()[$key] ?? null;
    }

    protected function replaceGlobalResearchLinks(array $config): void
    {
        $researchLinks = collect($config['columns'] ?? [])
            ->filter(fn ($column) => ($column['source'] ?? null) === 'research_links')
            ->flatMap(fn ($column) => $column['items'] ?? [])
            ->filter(fn ($link) => is_array($link) && filled($link['title'] ?? null) && filled($link['article_url'] ?? null))
            ->values()
            ->all();

        CmsResearchLink::query()
            ->whereNull('product_id')
            ->delete();

        foreach (array_values($researchLinks) as $index => $link) {
            CmsResearchLink::create([
                'product_id' => null,
                'title' => $link['title'],
                'article_url' => $link['article_url'],
                'authors' => $link['authors'] ?? null,
                'journal' => $link['journal'] ?? null,
                'publication_year' => $link['publication_year'] ?? null,
                'pubmed_id' => $link['pubmed_id'] ?? null,
                'doi' => $link['doi'] ?? null,
                'display_order' => Arr::get($link, 'display_order', $index),
            ]);
        }
    }
}
