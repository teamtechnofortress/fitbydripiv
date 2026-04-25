<?php

namespace App\Http\Controllers;

use App\Repositories\ProductRepository;
use App\Services\Content\Resolvers\ProductPageResolver;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected ProductService $productService,
        protected ProductPageResolver $productPageResolver
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

        $payload = $this->buildProductListPayload($products);

        Log::info('Admin products list response', [
            'filters' => $validated,
            'data' => $payload['data'],
            'meta' => $payload['meta'],
        ]);

        return response()->json($payload);
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

        $payload = $this->buildProductListPayload($products);

        Log::info('Admin draft products list response', [
            'filters' => $validated,
            'data' => $payload['data'],
            'meta' => $payload['meta'],
        ]);

        return response()->json($payload);
    }

    protected function buildProductListPayload($products): array
    {
        return [
            'success' => true,
            'data' => collect($products->items())->map(fn ($product) => $this->transformProductListItem($product))->values()->all(),
            'meta' => $this->paginationMeta($products),
        ];
    }

    public function destroy(string $productId): JsonResponse
    {
        $product = $this->productRepository->findById($productId);

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
            'data' => [
                'product_id' => $productId,
            ],
        ]);
    }

    public function publish(string $productId): JsonResponse
    {
        $product = $this->productService->publishProduct($productId);

        return response()->json([
            'success' => true,
            'message' => 'Product published successfully.',
            'data' => [
                'product_id' => $product->id,
                'is_published' => $product->is_published,
                'completion_status' => $product->completion_status,
            ],
        ]);
    }

    public function unpublish(string $productId): JsonResponse
    {
        $product = $this->productService->unpublishProduct($productId);

        return response()->json([
            'success' => true,
            'message' => 'Product unpublished successfully.',
            'data' => [
                'product_id' => $product->id,
                'is_published' => $product->is_published,
                'completion_status' => $product->completion_status,
            ],
        ]);
    }

    public function publishStatus(string $productId): JsonResponse
    {
        $product = $this->productRepository->findById($productId);

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'is_published' => $product->is_published,
                'completion_status' => $product->completion_status,
                'completion_percentage' => $product->completion_percentage,
                'completion_step' => $product->completion_step,
                'can_publish' => $product->completion_status === 'complete' && ! $product->is_published,
                'can_unpublish' => $product->is_published,
            ],
        ]);
    }

    public function preview(string $productId): JsonResponse
    {
        $preview = $this->productPageResolver->previewById($productId);

        if (! $preview) {
            return response()->json([
                'success' => false,
                'message' => 'Product preview not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $preview,
        ]);
    }

    protected function transformProductListItem(object $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
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
