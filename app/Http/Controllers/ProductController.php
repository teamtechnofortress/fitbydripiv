<?php

namespace App\Http\Controllers;

use App\Repositories\ProductRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRepository $productRepository
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'is_published' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $products = $this->productRepository->paginateAll(
            $validated,
            $validated['per_page'] ?? 15
        );

        Log::info('Admin products list fetched', [
            'filters' => $validated,
            'count' => count($products->items()),
            'current_page' => $products->currentPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
            'product_ids' => collect($products->items())->pluck('id')->values()->all(),
        ]);

        return response()->json([
            'success' => true,
            'data' => collect($products->items())->map(fn ($product) => $this->transformProductListItem($product))->values(),
            'meta' => $this->paginationMeta($products),
        ]);
    }

    public function drafts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $products = $this->productRepository->paginateDrafts(
            $validated,
            $validated['per_page'] ?? 15
        );

        Log::info('Admin draft products list fetched', [
            'filters' => $validated,
            'count' => count($products->items()),
            'current_page' => $products->currentPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
            'product_ids' => collect($products->items())->pluck('id')->values()->all(),
        ]);

        return response()->json([
            'success' => true,
            'data' => collect($products->items())->map(fn ($product) => $this->transformProductListItem($product))->values(),
            'meta' => $this->paginationMeta($products),
        ]);
    }

    protected function transformProductListItem(object $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->category,
            'description' => $product->description,
            'is_featured' => $product->is_featured,
            'is_published' => $product->is_published,
            'completion_status' => $product->completion_status,
            'completion_percentage' => $product->completion_percentage,
            'completion_step' => $product->completion_step,
            'cover_image' => $product->coverImage ? [
                'id' => $product->coverImage->id,
                'image_url' => $product->coverImage->image_url,
                'image_type' => $product->coverImage->image_type,
            ] : null,
            'counts' => [
                'images' => $product->images_count,
                'benefits' => $product->benefits_count,
                'faqs' => $product->faqs_count,
                'research_links' => $product->research_links_count,
                'pricing_groups' => $product->pricing_count,
            ],
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
        ];
    }

    protected function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
