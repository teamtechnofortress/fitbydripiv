<?php

namespace App\Http\Controllers;

use App\Models\CmsCategory;
use App\Models\CmsProduct;
use App\Models\CmsProductFaq;
use App\Models\CmsSiteSetting;
use App\Models\CmsContactSubmission;
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

    public function getProductsByCategory(string $slug): JsonResponse
    {
        $category = CmsCategory::where('slug', $slug)->first();
        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found'], 404);
        }
        $products = $category->products()->get();
        return response()->json(['success' => true, 'data' => $products]);
    }

    public function getFeaturedProducts(): JsonResponse
    {
        $products = CmsProduct::where('is_featured', true)
            ->orderByRaw("CASE WHEN slug = ? THEN 0 ELSE 1 END", ['tirzepatide'])
            ->orderBy('display_order')
            ->limit(6)
            ->get();

        Log::info('CMS featured products fetched', [
            'count' => $products->count(),
            'product_ids' => $products->pluck('id'),
        ]);

        return response()->json(['success' => true, 'data' => $products]);
    }

    public function getProductBySlug(string $slug): JsonResponse
    {
        $product = CmsProduct::where('slug', $slug)
            ->with(['category', 'researchLinks', 'pricingOptions', 'faqs' => function ($q) {
                $q->where('is_active', true);
            }, 'subscriptionDiscounts'])
            ->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $product]);
    }

    public function getProductPricing(string $slug): JsonResponse
    {
        $product = CmsProduct::where('slug', $slug)
            ->with('subscriptionDiscounts')
            ->first(['id', 'name', 'slug', 'base_price', 'micro_dose_price', 'sample_price', 'currency', 'short_description', 'featured_image', 'portrait_image', 'landscape_image']);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $product]);
    }

    public function getAllProductsForSelector(): JsonResponse
    {
        $products = CmsProduct::orderBy('display_order')
            ->get(['id', 'name', 'slug', 'featured_image', 'portrait_image', 'landscape_image', 'short_description']);
        return response()->json(['success' => true, 'data' => $products]);
    }

    public function getFaqs(): JsonResponse
    {
        $faqs = CmsProductFaq::where('is_active', true)
            ->orderBy('display_order')
            ->get();
        return response()->json(['success' => true, 'data' => $faqs]);
    }

    public function getFaqsByCategory(string $category): JsonResponse
    {
        $faqs = CmsProductFaq::where('category', $category)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
        return response()->json(['success' => true, 'data' => $faqs]);
    }

    public function getSiteSettings(): JsonResponse
    {
        $settings = CmsSiteSetting::find(1);
        return response()->json(['success' => true, 'data' => $settings]);
    }

    public function submitContact(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
            'submission_type' => 'sometimes|string|max:50',
            'product_id' => 'sometimes|nullable|uuid',
        ]);

        $submission = CmsContactSubmission::create($validated);
        return response()->json(['success' => true, 'data' => $submission], 201);
    }
}
