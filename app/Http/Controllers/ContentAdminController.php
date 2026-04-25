<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\SectionItem;
use App\Models\Setting;
use App\Services\Content\GlobalSectionService;
use App\Services\Content\SectionService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContentAdminController extends Controller
{
    public function __construct(
        protected SectionService $sectionService,
        protected SettingService $settingService,
        protected GlobalSectionService $globalSectionService
    ) {
    }

    public function getSettings(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->settingService->all(
                $request->filled('group') ? $request->string('group')->toString() : null
            ),
        ]);
    }

    public function saveSetting(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'sometimes|integer|exists:settings,id',
            'key' => 'required|string|max:255|in:' . implode(',', $this->settingService->allowedKeys()),
            'value' => 'nullable',
        ]);

        $setting = $this->settingService->save($validated);
        Cache::forget('layout:resolved');

        return response()->json(['success' => true, 'data' => $setting]);
    }

    public function deleteSetting(int $id): JsonResponse
    {
        Setting::findOrFail($id)->delete();
        Cache::forget('layout:resolved');

        return response()->json(['success' => true, 'message' => 'Setting deleted']);
    }

    public function getPages(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Page::with(['sections.items', 'sections.faqs'])->orderBy('title')->get(),
        ]);
    }

    public function getGlobalSections(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->globalSectionService->all(),
        ]);
    }

    public function saveGlobalSection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'sometimes|uuid|exists:global_sections,id',
            'key' => 'required|string|in:' . implode(',', $this->globalSectionService->allowedKeys()),
            'config' => 'nullable|array',
        ]);

        $this->validateGlobalSectionConfig(
            $validated['key'],
            is_array($validated['config'] ?? null) ? $validated['config'] : []
        );

        $section = $this->globalSectionService->save($validated);
        Cache::forget('layout:resolved');

        return response()->json([
            'success' => true,
            'data' => $section,
        ]);
    }

    public function getPage(string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Page::with(['sections.items', 'sections.faqs'])->findOrFail($id),
        ]);
    }

    public function getPageSections(string $pageId): JsonResponse
    {
        Page::findOrFail($pageId);

        return response()->json([
            'success' => true,
            'data' => PageSection::with(['items', 'faqs'])
                ->where('page_id', $pageId)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function savePage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'sometimes|uuid|exists:pages,id',
            'slug' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'status' => 'nullable|in:draft,published',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        $page = isset($validated['id'])
            ? Page::findOrFail($validated['id'])
            : new Page();

        $page->fill($validated);
        $page->status = $validated['status'] ?? 'draft';
        $page->save();

        return response()->json(['success' => true, 'data' => $page]);
    }

    public function deletePage(string $id): JsonResponse
    {
        Page::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Page deleted']);
    }

    public function saveSection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'sometimes|uuid|exists:page_sections,id',
            'page_id' => 'required|uuid|exists:pages,id',
            'section_key' => 'required|string|max:150',
            'type' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'content' => 'nullable|array',
            'image' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'required|string',
            'faqs.*.answer' => 'required|string',
            'faqs.*.sort_order' => 'nullable|integer',
            'faqs.*.is_active' => 'nullable|boolean',
        ]);

        $section = $this->sectionService->store($validated);

        return response()->json(['success' => true, 'data' => $section]);
    }

    public function createPageSection(Request $request, string $pageId): JsonResponse
    {
        $page = Page::findOrFail($pageId);

        $validated = $request->validate([
            'section_key' => 'required|string|max:150',
            'type' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'content' => 'nullable|array',
            'image' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'required|string',
            'faqs.*.answer' => 'required|string',
            'faqs.*.sort_order' => 'nullable|integer',
            'faqs.*.is_active' => 'nullable|boolean',
        ]);

        $section = $this->sectionService->store([
            ...$validated,
            'page_id' => $page->id,
            'sort_order' => $validated['sort_order']
                ?? ((int) PageSection::where('page_id', $page->id)->max('sort_order') + 1),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Section created successfully.',
            'data' => $section,
        ], 201);
    }

    public function deleteSection(string $id): JsonResponse
    {
        PageSection::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Section deleted']);
    }

    public function saveSectionItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'sometimes|uuid|exists:section_items,id',
            'section_id' => 'required|uuid|exists:page_sections,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:500',
            'image' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
        ]);

        $item = isset($validated['id'])
            ? SectionItem::findOrFail($validated['id'])
            : new SectionItem();

        $item->fill($validated);
        $item->sort_order = $validated['sort_order'] ?? 0;
        $item->save();

        return response()->json(['success' => true, 'data' => $item]);
    }

    public function reorderSections(Request $request, string $pageId): JsonResponse
    {
        $validated = $request->validate([
            'sections' => 'required|array|min:1',
            'sections.*.id' => 'required|uuid|exists:page_sections,id',
            'sections.*.sort_order' => 'required|integer',
        ]);

        Page::findOrFail($pageId);

        DB::transaction(function () use ($pageId, $validated) {
            foreach ($validated['sections'] as $section) {
                PageSection::where('page_id', $pageId)
                    ->where('id', $section['id'])
                    ->update(['sort_order' => $section['sort_order']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Sections reordered successfully.',
            'data' => PageSection::where('page_id', $pageId)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function reorderSectionItems(Request $request, string $sectionId): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|uuid|exists:section_items,id',
            'items.*.sort_order' => 'required|integer',
        ]);

        PageSection::findOrFail($sectionId);

        DB::transaction(function () use ($sectionId, $validated) {
            foreach ($validated['items'] as $item) {
                SectionItem::where('section_id', $sectionId)
                    ->where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Section items reordered successfully.',
            'data' => SectionItem::where('section_id', $sectionId)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function deleteSectionItem(string $id): JsonResponse
    {
        SectionItem::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Section item deleted']);
    }

    protected function validateGlobalSectionConfig(string $key, array $config): void
    {
        if ($key === 'header') {
            $this->validateHeaderConfig($config);

            return;
        }

        if ($key === 'footer') {
            $this->validateFooterConfig($config);
        }
    }

    protected function validateHeaderConfig(array $config): void
    {
        if (isset($config['menu']) && ! is_array($config['menu'])) {
            throw ValidationException::withMessages([
                'config.menu' => 'Header menu must be an array.',
            ]);
        }

        foreach ($config['menu'] ?? [] as $index => $item) {
            if (! is_array($item)) {
                throw ValidationException::withMessages([
                    "config.menu.$index" => 'Each menu item must be an object.',
                ]);
            }

            $type = $item['type'] ?? null;

            if (! in_array($type, ['group', 'link'], true)) {
                throw ValidationException::withMessages([
                    "config.menu.$index.type" => 'Header menu item type must be group or link.',
                ]);
            }

            if (blank($item['label'] ?? null)) {
                throw ValidationException::withMessages([
                    "config.menu.$index.label" => 'Header menu item label is required.',
                ]);
            }

            if ($type === 'group') {
                $source = $item['source'] ?? (isset($item['items']) ? 'manual' : null);

                if (! in_array($source, ['categories', 'research_links', 'static_pages', 'social_links', 'manual'], true)) {
                    throw ValidationException::withMessages([
                        "config.menu.$index.source" => 'Header group source is invalid.',
                    ]);
                }

                if ($source === 'manual' && ! is_array($item['items'] ?? null)) {
                    throw ValidationException::withMessages([
                        "config.menu.$index.items" => 'Manual header groups require an items array.',
                    ]);
                }

                continue;
            }

            if (blank($item['slug'] ?? null) && blank($item['href'] ?? null) && blank($item['url'] ?? null)) {
                throw ValidationException::withMessages([
                    "config.menu.$index" => 'Header links require a slug or href.',
                ]);
            }
        }
    }

    protected function validateFooterConfig(array $config): void
    {
        if (isset($config['columns']) && ! is_array($config['columns'])) {
            throw ValidationException::withMessages([
                'config.columns' => 'Footer columns must be an array.',
            ]);
        }

        foreach ($config['columns'] ?? [] as $index => $column) {
            if (! is_array($column)) {
                throw ValidationException::withMessages([
                    "config.columns.$index" => 'Each footer column must be an object.',
                ]);
            }

            $source = $column['source'] ?? null;

            if ($source !== null && ! in_array($source, ['brand', 'categories', 'static_pages', 'research_links', 'social_links', 'manual'], true)) {
                throw ValidationException::withMessages([
                    "config.columns.$index.source" => 'Footer column source is invalid.',
                ]);
            }

            if (($source === 'static_pages' || $source === 'manual') && ! is_array($column['items'] ?? null)) {
                throw ValidationException::withMessages([
                    "config.columns.$index.items" => 'This footer column requires an items array.',
                ]);
            }

            if ($source === 'research_links') {
                if (! is_array($column['items'] ?? null)) {
                    throw ValidationException::withMessages([
                        "config.columns.$index.items" => 'Research footer columns require an items array.',
                    ]);
                }

                foreach ($column['items'] as $itemIndex => $item) {
                    if (! is_array($item)) {
                        throw ValidationException::withMessages([
                            "config.columns.$index.items.$itemIndex" => 'Each research link must be an object.',
                        ]);
                    }

                    if (blank($item['title'] ?? null)) {
                        throw ValidationException::withMessages([
                            "config.columns.$index.items.$itemIndex.title" => 'Research link title is required.',
                        ]);
                    }

                    if (blank($item['article_url'] ?? null)) {
                        throw ValidationException::withMessages([
                            "config.columns.$index.items.$itemIndex.article_url" => 'Research link article_url is required.',
                        ]);
                    }
                }
            }
        }
    }
}
