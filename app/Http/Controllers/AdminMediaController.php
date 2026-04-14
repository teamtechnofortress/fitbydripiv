<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminMediaController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
            'type' => 'nullable|in:product,user,misc',
        ]);

        $folder = match ($validated['type'] ?? 'misc') {
            'product' => 'admin/products',
            'user' => 'admin/users',
            default => 'admin/misc',
        };

        $file = $request->file('file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($folder, $filename, 'public');

        return response()->json([
            'success' => true,
            'data' => [
                'url' => Storage::disk('public')->url($path),
                'path' => $path,
                'type' => $validated['type'] ?? 'misc',
                'original_name' => $file->getClientOriginalName(),
            ],
        ]);
    }
}
