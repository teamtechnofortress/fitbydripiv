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

            $isAllowedPdf = $extension === 'pdf'
                && (
                    in_array($detectedMimeType, ['application/pdf', 'application/octet-stream', ''], true)
                    || in_array($clientMimeType, ['application/pdf', 'application/octet-stream', ''], true)
                );

            if (! $isAllowedImage && ! $isAllowedVideo && ! $isAllowedPdf) {
                throw ValidationException::withMessages([
                    'file' => 'The file must be a valid jpg, jpeg, png, webp, mp4, webm, mov, or pdf upload.',
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
            $isPdf = $isAllowedPdf;

            $folder = match (true) {
                $isVideo => 'admin/videos',
                $isPdf => 'admin/documents',
                ($validated['type'] ?? null) === 'product' => 'admin/products',
                ($validated['type'] ?? null) === 'user' => 'admin/users',
                default => 'admin/misc',
            };

            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            Log::info('Admin media upload storing file', [
                'folder' => $folder,
                'filename' => $filename,
                'disk' => 'public',
                'media_type' => $isVideo ? 'video' : ($isPdf ? 'pdf' : 'image'),
                'mime_type' => $mimeType,
            ]);

            $path = $file->storeAs($folder, $filename, 'public');

            $response = [
                'success' => true,
                'data' => [
                    'url' => Storage::disk('public')->url($path),
                    'path' => $path,
                    'type' => $validated['type'] ?? 'misc',
                    'media_type' => $isVideo ? 'video' : ($isPdf ? 'pdf' : 'image'),
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

    public function downloadPublicPdf(Request $request)
    {
        $validated = $request->validate([
            'path' => 'required|string|max:2048',
            'name' => 'nullable|string|max:255',
        ]);

        $path = $this->normalizePublicDocumentPath($validated['path']);

        if (! filled($path)) {
            throw ValidationException::withMessages([
                'path' => 'Invalid document path.',
            ]);
        }

        if (! Str::startsWith($path, 'admin/documents/')) {
            throw ValidationException::withMessages([
                'path' => 'Only admin document PDFs can be downloaded from this endpoint.',
            ]);
        }

        if (! Str::endsWith(Str::lower($path), '.pdf')) {
            throw ValidationException::withMessages([
                'path' => 'Only PDF files are allowed.',
            ]);
        }

        if (! Storage::disk('public')->exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'PDF file not found.',
            ], 404);
        }

        $downloadName = $this->resolveDownloadFileName($validated['name'] ?? null, $path);

        return Storage::disk('public')->download($path, $downloadName, ['Content-Type' => 'application/pdf']);
    }

    private function normalizePublicDocumentPath(string $path): ?string
    {
        $normalizedPath = trim(rawurldecode($path));

        if ($normalizedPath === '') {
            return null;
        }

        if (Str::startsWith($normalizedPath, ['http://', 'https://'])) {
            $parsedPath = parse_url($normalizedPath, PHP_URL_PATH);
            $normalizedPath = is_string($parsedPath) ? $parsedPath : '';
        }

        $normalizedPath = str_replace('\\', '/', ltrim($normalizedPath, '/'));

        if (Str::startsWith($normalizedPath, 'storage/')) {
            $normalizedPath = Str::after($normalizedPath, 'storage/');
        }

        while (str_contains($normalizedPath, '//')) {
            $normalizedPath = str_replace('//', '/', $normalizedPath);
        }

        if ($normalizedPath === '' || str_contains($normalizedPath, '..') || str_contains($normalizedPath, "\0")) {
            return null;
        }

        return $normalizedPath;
    }

    private function resolveDownloadFileName(?string $requestedName, string $path): string
    {
        $fallbackName = basename($path);
        $name = trim((string) $requestedName);

        if ($name === '') {
            return $fallbackName;
        }

        $name = str_replace(["\0", '/', '\\'], '', $name);
        $name = preg_replace('/\s+/', ' ', $name) ?? '';
        $name = trim($name);

        if ($name === '' || $name === '.' || $name === '..') {
            return $fallbackName;
        }

        if (! Str::endsWith(Str::lower($name), '.pdf')) {
            $name .= '.pdf';
        }

        return $name;
    }
}
