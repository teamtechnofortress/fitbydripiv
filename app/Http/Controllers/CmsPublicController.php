<?php

namespace App\Http\Controllers;

use App\Models\CmsCategory;
use App\Models\CmsProduct;
use App\Models\CmsProductFaq;
use App\Models\CmsSiteSetting;
use App\Models\CmsContactSubmission;
use App\Models\Product;
use App\Models\ProductPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CmsPublicController extends Controller
{
    public function getCategories(): JsonResponse
    {
        $categories = CmsCategory::orderBy('display_order')->get();
        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function getCategoryBySlug(string $slug): JsonResponse
    {
        $category = CmsCategory::where('slug', $slug)->first();
        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $category]);
    }

    // public function getProductsByCategory(string $slug): JsonResponse
    // {
    //     $category = CmsCategory::where('slug', $slug)->first();
    //     if (!$category) {
    //         return response()->json(['success' => false, 'message' => 'Category not found'], 404);
    //     }
    //     $products = $category->products()->get();
    //     return response()->json(['success' => true, 'data' => $products]);
    // }

    // public function getFeaturedProducts(): JsonResponse
    // {
    //     $products = CmsProduct::where('is_featured', true)
    //         ->orderByRaw("CASE WHEN slug = ? THEN 0 ELSE 1 END", ['tirzepatide'])
    //         ->orderBy('display_order')
    //         ->limit(6)
    //         ->get();

    //     Log::info('CMS featured products fetched', [
    //         'count' => $products->count(),
    //         'product_ids' => $products->pluck('id'),
    //     ]);

    //     return response()->json(['success' => true, 'data' => $products]);
    // }

    public function getProductBySlug(string $slug): JsonResponse
    {
        $product = Product::query()
            ->with([
                'coverImage',
                'images',
                'benefits',
                'researchLinks',
                'ingredients',
                'faqs' => function ($q) {
                $q->where('is_active', true);
            },
            ])
            ->live()
            ->where('slug', $slug)
            ->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $product]);
    }

    public function getProductPricing(string $slug): JsonResponse
    {
        $product = Product::query()
            ->with([
                'coverImage',
                'pricing' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with(['options' => fn ($options) => $options->orderBy('sort_order')]),
            ])
            ->live()
            ->where('slug', $slug)
            ->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $pricingLayers = $product->pricing
            ->keyBy('pricing_type');

        return response()->json([
            'success' => true,
            'data' => [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'category' => $product->category,
                    'description' => $product->description,
                    'cover_image' => $product->coverImage ? [
                        'id' => $product->coverImage->id,
                        'image_url' => $product->coverImage->image_url,
                        'image_type' => $product->coverImage->image_type,
                    ] : null,
                ],
                'pricing' => [
                    'one_time' => $this->transformPricingLayer($pricingLayers->get('one_time')),
                    'subscription' => $this->transformPricingLayer($pricingLayers->get('subscription')),
                ],
            ],
        ]);
    }

    // public function getAllProductsForSelector(): JsonResponse
    // {
    //     $products = CmsProduct::orderBy('display_order')
    //         ->get(['id', 'name', 'slug', 'featured_image', 'portrait_image', 'landscape_image', 'short_description']);
    //     return response()->json(['success' => true, 'data' => $products]);
    // }

    // public function getFaqs(): JsonResponse
    // {
    //     $faqs = CmsProductFaq::where('is_active', true)
    //         ->orderBy('display_order')
    //         ->get();
    //     return response()->json(['success' => true, 'data' => $faqs]);
    // }

    // public function getFaqsByCategory(string $category): JsonResponse
    // {
    //     $faqs = CmsProductFaq::where('category', $category)
    //         ->where('is_active', true)
    //         ->orderBy('display_order')
    //         ->get();
    //     return response()->json(['success' => true, 'data' => $faqs]);
    // }

    // public function getSiteSettings(): JsonResponse
    // {
    //     $settings = CmsSiteSetting::find(1);
    //     return response()->json(['success' => true, 'data' => $settings]);
    // }

    // public function submitContact(Request $request): JsonResponse
    // {
    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|email|max:255',
    //         'message' => 'required|string',
    //         'submission_type' => 'sometimes|string|max:50',
    //         'product_id' => 'sometimes|nullable|uuid',
    //     ]);

    //     $submission = CmsContactSubmission::create($validated);
    //     return response()->json(['success' => true, 'data' => $submission], 201);
    // }

    protected function transformPricingLayer(?ProductPricing $pricing): ?array
    {
        if (! $pricing) {
            return null;
        }

        $options = $pricing->options
            ->map(fn ($option) => [
                'id' => $option->id,
                'label' => $option->label,
                'billing_interval' => $option->billing_interval,
                'interval_count' => $option->interval_count,
                'price' => $option->price,
                'discount_percent' => $option->discount_percent,
                'final_price' => $option->final_price,
                'sort_order' => $option->sort_order,
                'is_default' => $option->is_default,
                'metadata' => $option->metadata,
            ])
            ->values();

        return [
            'id' => $pricing->id,
            'pricing_type' => $pricing->pricing_type,
            'title' => $pricing->title,
            'description' => $pricing->description,
            'is_active' => $pricing->is_active,
            'default_option' => $options->firstWhere('is_default', true) ?? $options->first(),
            'options' => $options->all(),
        ];
    }
}
