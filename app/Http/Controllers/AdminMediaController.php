<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminMediaController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        Log::info('Admin media upload request received', [
            'type' => $request->input('type'),
            'has_file' => $request->hasFile('file'),
            'content_type' => $request->header('Content-Type'),
            'content_length' => $request->server('CONTENT_LENGTH'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ]);

        try {
            $validated = $request->validate([
                'file' => 'required|file|max:10240',
                'type' => 'nullable|in:product,user,misc',
            ]);

            $file = $request->file('file');
            $extension = strtolower($file?->getClientOriginalExtension() ?? '');
            $clientMimeType = (string) $file?->getClientMimeType();
            $detectedMimeType = (string) $file?->getMimeType();

            $isAllowedImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)
                && (
                    str_starts_with($clientMimeType, 'image/')
                    || str_starts_with($detectedMimeType, 'image/')
                );

            $isAllowedVideo = in_array($extension, ['mp4', 'webm', 'mov'], true)
                && (
                    str_starts_with($clientMimeType, 'video/')
                    || str_starts_with($detectedMimeType, 'video/')
                    || in_array($detectedMimeType, ['application/mp4', 'application/octet-stream'], true)
                );

            if (! $isAllowedImage && ! $isAllowedVideo) {
                throw ValidationException::withMessages([
                    'file' => 'The file must be a valid jpg, jpeg, png, webp, mp4, webm, or mov upload.',
                ]);
            }

            Log::info('Admin media upload validated', [
                'type' => $validated['type'] ?? 'misc',
                'original_name' => $file?->getClientOriginalName(),
                'client_mime_type' => $clientMimeType,
                'detected_mime_type' => $detectedMimeType,
                'extension' => $extension,
                'size' => $file?->getSize(),
            ]);

            $mimeType = $detectedMimeType !== '' ? $detectedMimeType : $clientMimeType;
            $isVideo = $isAllowedVideo;

            $folder = match (true) {
                $isVideo => 'admin/videos',
                ($validated['type'] ?? null) === 'product' => 'admin/products',
                ($validated['type'] ?? null) === 'user' => 'admin/users',
                default => 'admin/misc',
            };

            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            Log::info('Admin media upload storing file', [
                'folder' => $folder,
                'filename' => $filename,
                'disk' => 'public',
                'media_type' => $isVideo ? 'video' : 'image',
                'mime_type' => $mimeType,
            ]);

            $path = $file->storeAs($folder, $filename, 'public');

            $response = [
                'success' => true,
                'data' => [
                    'url' => Storage::disk('public')->url($path),
                    'path' => $path,
                    'type' => $validated['type'] ?? 'misc',
                    'media_type' => $isVideo ? 'video' : 'image',
                    'mime_type' => $mimeType,
                    'extension' => $extension,
                    'size' => $file->getSize(),
                    'original_name' => $file->getClientOriginalName(),
                ],
            ];

            Log::info('Admin media upload completed', $response['data']);

            return response()->json($response);
        } catch (Throwable $exception) {
            Log::error('Admin media upload failed', [
                'type' => $request->input('type'),
                'has_file' => $request->hasFile('file'),
                'original_name' => $request->file('file')?->getClientOriginalName(),
                'client_mime_type' => $request->file('file')?->getClientMimeType(),
                'detected_mime_type' => $request->file('file')?->getMimeType(),
                'extension' => strtolower($request->file('file')?->getClientOriginalExtension() ?? ''),
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
            ]);

            throw $exception;
        }
    }
}
