<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CmsUploadController extends Controller
{
    public function uploadProductImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg,webp|max:5120',
            'type' => 'required|in:landscape,portrait,featured',
            'product_slug' => 'required|string',
        ]);

        $file = $request->file('image');
        $ext = $file->getClientOriginalExtension();
        $path = "cms/products/{$request->type}/{$request->product_slug}.{$ext}";

        Storage::disk('public')->put($path, file_get_contents($file));
        $url = Storage::disk('public')->url($path);

        return response()->json(['success' => true, 'data' => ['url' => $url, 'path' => $path]]);
    }

    public function uploadCategoryVideo(Request $request): JsonResponse
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,webm,mov|max:51200',
            'category_slug' => 'required|string',
        ]);

        $file = $request->file('video');
        $ext = $file->getClientOriginalExtension();
        $path = "cms/categories/{$request->category_slug}.{$ext}";

        Storage::disk('public')->put($path, file_get_contents($file));
        $url = Storage::disk('public')->url($path);

        return response()->json(['success' => true, 'data' => ['url' => $url, 'path' => $path]]);
    }

    public function uploadHeroVideo(Request $request): JsonResponse
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,webm,mov|max:51200',
        ]);

        $file = $request->file('video');
        $ext = $file->getClientOriginalExtension();
        $path = "cms/hero/hero-video.{$ext}";

        Storage::disk('public')->put($path, file_get_contents($file));
        $url = Storage::disk('public')->url($path);

        return response()->json(['success' => true, 'data' => ['url' => $url, 'path' => $path]]);
    }
}
