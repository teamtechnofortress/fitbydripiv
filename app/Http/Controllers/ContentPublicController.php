<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\Content\PageResolver;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentPublicController extends Controller
{
    public function __construct(
        protected PageResolver $pageResolver,
        protected SettingService $settingService
    ) {
    }

    public function getPages(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Page::published()
                ->orderBy('title')
                ->get(['id', 'slug', 'title', 'meta_title', 'meta_description']),
        ]);
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

    public function getPageBySlug(string $slug): JsonResponse
    {
        $data = $this->pageResolver->resolve($slug);

        if (! $data) {
            return response()->json(['success' => false, 'message' => 'Page not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
