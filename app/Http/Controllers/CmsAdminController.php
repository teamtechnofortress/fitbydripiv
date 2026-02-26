<?php

namespace App\Http\Controllers;

use App\Models\CmsCategory;
use App\Models\CmsProduct;
use App\Models\CmsResearchLink;
use App\Models\CmsPricingOption;
use App\Models\CmsProductFaq;
use App\Models\CmsSubscriptionDiscount;
use App\Models\CmsSiteSetting;
use App\Models\CmsContactSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CmsAdminController extends Controller
{
    // Categories
    public function getCategories(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => CmsCategory::orderBy('display_order')->get()]);
    }

    public function saveCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'sometimes|uuid',
            'name' => 'required|string|max:255',
            'slug' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'banner_image' => 'nullable|string',
            'landscape_banner' => 'nullable|string',
            'background_video' => 'nullable|string',
            'video_playback_speed' => 'nullable|numeric|min:0.5|max:3.0',
            'display_order' => 'nullable|integer',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category = isset($validated['id'])
            ? CmsCategory::findOrFail($validated['id'])
            : new CmsCategory();

        $category->fill($validated);
        $category->save();

        return response()->json(['success' => true, 'data' => $category]);
    }

    public function deleteCategory(string $id): JsonResponse
    {
        CmsCategory::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Category deleted']);
    }

    // Products
    public function getProducts(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => CmsProduct::with([
                'category',
                'researchLinks',
                'pricingOptions',
                'faqs',
                'subscriptionDiscounts',
            ])->orderBy('display_order')->get()
        ]);
    }

    public function saveProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'sometimes|uuid',
            'category_id' => 'required|uuid|exists:cms_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'sometimes|string|max:255',
            'short_description' => 'nullable|string',
            'full_description' => 'nullable|string',
            'benefits' => 'nullable|array',
            'treatment_details' => 'nullable|array',
            'featured_image' => 'nullable|string',
            'portrait_image' => 'nullable|string',
            'landscape_image' => 'nullable|string',
            'image_gallery' => 'nullable|array',
            'base_price' => 'nullable|numeric|min:0',
            'micro_dose_price' => 'nullable|numeric|min:0',
            'sample_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'display_order' => 'nullable|integer',
            'is_featured' => 'nullable|boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $product = isset($validated['id'])
            ? CmsProduct::findOrFail($validated['id'])
            : new CmsProduct();

        $product->fill($validated);
        $product->save();

        return response()->json(['success' => true, 'data' => $product->load('category')]);
    }

    public function deleteProduct(string $id): JsonResponse
    {
        CmsProduct::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Product deleted']);
    }

    // Research Links
    public function saveResearchLink(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'sometimes|uuid',
            'product_id' => 'required|uuid|exists:cms_products,id',
            'title' => 'required|string|max:255',
            'authors' => 'nullable|string',
            'journal' => 'nullable|string',
            'publication_year' => 'nullable|integer',
            'pubmed_id' => 'nullable|string',
            'doi' => 'nullable|string',
            'article_url' => 'nullable|string',
            'display_order' => 'nullable|integer',
        ]);

        $link = isset($validated['id'])
            ? CmsResearchLink::findOrFail($validated['id'])
            : new CmsResearchLink();

        $link->fill($validated);
        $link->save();

        return response()->json(['success' => true, 'data' => $link]);
    }

    public function deleteResearchLink(string $id): JsonResponse
    {
        CmsResearchLink::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Research link deleted']);
    }

    // Pricing Options
    public function savePricingOption(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'sometimes|uuid',
            'product_id' => 'required|uuid|exists:cms_products,id',
            'plan_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'nullable|string',
            'supply_duration' => 'nullable|string',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'is_popular' => 'nullable|boolean',
            'display_order' => 'nullable|integer',
        ]);

        $option = isset($validated['id'])
            ? CmsPricingOption::findOrFail($validated['id'])
            : new CmsPricingOption();

        $option->fill($validated);
        $option->save();

        return response()->json(['success' => true, 'data' => $option]);
    }

    public function deletePricingOption(string $id): JsonResponse
    {
        CmsPricingOption::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Pricing option deleted']);
    }

    // FAQs
    public function saveFaq(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'sometimes|uuid',
            'product_id' => 'nullable|uuid|exists:cms_products,id',
            'category' => 'nullable|string|max:100',
            'question' => 'required|string',
            'answer' => 'required|string',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $faq = isset($validated['id'])
            ? CmsProductFaq::findOrFail($validated['id'])
            : new CmsProductFaq();

        $faq->fill($validated);
        $faq->save();

        return response()->json(['success' => true, 'data' => $faq]);
    }

    public function deleteFaq(string $id): JsonResponse
    {
        CmsProductFaq::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'FAQ deleted']);
    }

    // Subscription Discounts
    public function saveSubscriptionDiscount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'sometimes|uuid',
            'product_id' => 'required|uuid|exists:cms_products,id',
            'frequency_months' => 'required|integer|in:1,2,3',
            'discount_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $discount = isset($validated['id'])
            ? CmsSubscriptionDiscount::findOrFail($validated['id'])
            : new CmsSubscriptionDiscount();

        $discount->fill($validated);
        $discount->save();

        return response()->json(['success' => true, 'data' => $discount]);
    }

    public function deleteSubscriptionDiscount(string $id): JsonResponse
    {
        CmsSubscriptionDiscount::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Subscription discount deleted']);
    }

    // Site Settings
    public function saveSiteSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hero_video_url' => 'nullable|string',
            'hero_poster_image' => 'nullable|string',
            'hero_video_playback_speed' => 'nullable|numeric|min:0.5|max:3.0',
        ]);

        $settings = CmsSiteSetting::firstOrCreate(['id' => 1]);
        $settings->update($validated);

        return response()->json(['success' => true, 'data' => $settings]);
    }

    // Contact Submissions
    public function getContactSubmissions(Request $request): JsonResponse
    {
        $query = CmsContactSubmission::orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(['success' => true, 'data' => $query->paginate(20)]);
    }

    public function updateContactStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate(['status' => 'required|string|in:new,read,replied,archived']);
        $submission = CmsContactSubmission::findOrFail($id);
        $submission->update($validated);
        return response()->json(['success' => true, 'data' => $submission]);
    }
}
