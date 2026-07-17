<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConvertDocumentRequest;
use App\Services\Content\DocxToHtmlConverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class DocumentConversionController extends Controller
{
    public function __construct(
        private readonly DocxToHtmlConverter $converter,
    ) {}

    public function store(ConvertDocumentRequest $request): JsonResponse
    {
        try {
            $result = $this->converter->convert($request->file('document'));

            Log::info('Document import converted HTML.', [
                'source_filename' => $result->sourceFilename,
                'html_length' => strlen($result->html),
                'html' => $result->html,
                'warnings' => $result->warnings,
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'message' => 'Document converted successfully.',
                'data' => $result->toArray(),
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'The document could not be converted.',
            ], 500);
        }
    }
}
