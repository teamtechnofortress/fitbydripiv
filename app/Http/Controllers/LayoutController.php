<?php

namespace App\Http\Controllers;

use App\Services\Content\GlobalSectionService;
use App\Support\Content\Globals\GlobalSectionResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class LayoutController extends Controller
{
    public function __construct(
        protected GlobalSectionService $globalSectionService
    ) {
    }

    public function show(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Cache::remember('layout:resolved', now()->addMinutes(5), function () {
                $sections = $this->globalSectionService->all();

                return GlobalSectionResolver::resolveCollection($sections);
            }),
        ]);
    }
}
