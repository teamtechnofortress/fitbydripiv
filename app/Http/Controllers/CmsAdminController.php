<?php

namespace App\Http\Controllers;

use App\Models\CmsCategory;
use App\Models\CmsContactSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CmsAdminController extends Controller
{
    // Categories
    public function getCategories(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => CmsCategory::orderBy('display_order')->get()]);
    }

    public function saveCategory(Request $request): JsonResponse
    {
        $categoryId = $request->input('id');

        $validated = $request->validate([
            'id' => 'sometimes|uuid',
            'name' => 'required|string|max:255',
            'slug' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'banner_image' => 'nullable|string',
            'landscape_banner' => 'nullable|string',
            'background_video' => 'nullable|string',
            'video_playback_speed' => 'nullable|numeric|min:0.5|max:3.0',
            'display_order' => [
                'nullable',
                'integer',
                Rule::unique('cms_categories', 'display_order')->ignore($categoryId),
            ],
        ], [
            'display_order.unique' => 'Sort order must be unique. This value is already in use.',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category = isset($validated['id'])
            ? CmsCategory::findOrFail($validated['id'])
            : new CmsCategory();

        $category->fill($validated);
        $category->save();

        return response()->json(['success' => true, 'data' => $category]);
    }

    public function deleteCategory(string $id): JsonResponse
    {
        CmsCategory::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Category deleted']);
    }
    // Site Settings
    public function saveSiteSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hero_video_url' => 'nullable|string',
            'hero_poster_image' => 'nullable|string',
            'hero_video_playback_speed' => 'nullable|numeric|min:0.5|max:3.0',
        ]);

        $settings = CmsSiteSetting::firstOrCreate(['id' => 1]);
        $settings->update($validated);

        return response()->json(['success' => true, 'data' => $settings]);
    }

    // Contact Submissions
    public function getContactSubmissions(Request $request): JsonResponse
    {
        $query = CmsContactSubmission::orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(['success' => true, 'data' => $query->paginate(20)]);
    }

    public function updateContactStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate(['status' => 'required|string|in:new,read,replied,archived']);
        $submission = CmsContactSubmission::findOrFail($id);
        $submission->update($validated);
        return response()->json(['success' => true, 'data' => $submission]);
    }
}
