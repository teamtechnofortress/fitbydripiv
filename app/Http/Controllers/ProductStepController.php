<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductStep2Request;
use App\Http\Requests\StoreProductStep3Request;
use App\Http\Requests\StoreProductStep4Request;
use App\Http\Requests\StoreProductStep5Request;
use App\Http\Requests\StoreProductStep1Request;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductStepController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {
    }

    public function step1(StoreProductStep1Request $request): JsonResponse
    {
        $product = $this->productService->handleStep1($request->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'completion_status' => $product->completion_status,
                'completion_percentage' => $product->completion_percentage,
                'completion_step' => $product->completion_step,
                'cover_image_id' => $product->cover_image_id,
            ],
        ]);
    }

    public function getStep1(string $productId): JsonResponse
    {
        $product = $this->productService->getStep1Data($productId);

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'description' => $product->description,
                'cover_image_id' => $product->cover_image_id,
                'images' => $product->images->map(fn ($image) => [
                    'id' => $image->id,
                    'image_url' => $image->image_url,
                    'image_type' => $image->image_type,
                    'slot_position' => $image->slot_position,
                    'sort_order' => $image->sort_order,
                ])->values(),
                'completion_status' => $product->completion_status,
                'completion_percentage' => $product->completion_percentage,
                'completion_step' => $product->completion_step,
            ],
        ]);
    }

    public function step2(StoreProductStep2Request $request): JsonResponse
    {
        $product = $this->productService->handleStep2($request->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'completion_status' => $product->completion_status,
                'completion_percentage' => $product->completion_percentage,
                'completion_step' => $product->completion_step,
                'benefits_count' => $product->benefits->count(),
                'faqs_count' => $product->faqs->count(),
            ],
        ]);
    }

    public function getStep2(string $productId): JsonResponse
    {
        $product = $this->productService->getStep2Data($productId);

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'benefits' => $product->benefits->map(fn ($benefit) => [
                    'id' => $benefit->id,
                    'benefit_text' => $benefit->benefit_text,
                    'sort_order' => $benefit->sort_order,
                ])->values(),
                'faqs' => $product->faqs->map(fn ($faq) => [
                    'id' => $faq->id,
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                    'sort_order' => $faq->sort_order,
                ])->values(),
                'completion_status' => $product->completion_status,
                'completion_percentage' => $product->completion_percentage,
                'completion_step' => $product->completion_step,
            ],
        ]);
    }

    public function step3(StoreProductStep3Request $request): JsonResponse
    {
        $product = $this->productService->handleStep3($request->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'completion_status' => $product->completion_status,
                'completion_percentage' => $product->completion_percentage,
                'completion_step' => $product->completion_step,
                'ingredients_count' => $product->ingredients->count(),
            ],
        ]);
    }

    public function getStep3(string $productId): JsonResponse
    {
        $product = $this->productService->getStep3Data($productId);

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'about_treatment' => $product->about_treatment,
                'how_it_works' => $product->how_it_works,
                'treatment_duration' => $product->treatment_duration,
                'usage_instructions' => $product->usage_instructions,
                'ingredients' => $product->ingredients->map(fn ($ingredient) => [
                    'ingredient_id' => $ingredient->id,
                    'name' => $ingredient->name,
                    'description' => $ingredient->description,
                    'sort_order' => $ingredient->pivot->sort_order,
                ])->values(),
                'completion_status' => $product->completion_status,
                'completion_percentage' => $product->completion_percentage,
                'completion_step' => $product->completion_step,
            ],
        ]);
    }

    public function step4(StoreProductStep4Request $request): JsonResponse
    {
        $product = $this->productService->handleStep4($request->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'completion_status' => $product->completion_status,
                'completion_percentage' => $product->completion_percentage,
                'completion_step' => $product->completion_step,
                'clinical_research_description' => $product->clinical_research_description,
                'research_links_count' => $product->researchLinks->count(),
            ],
        ]);
    }

    public function getStep4(string $productId): JsonResponse
    {
        $product = $this->productService->getStep4Data($productId);

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'clinical_research_description' => $product->clinical_research_description,
                'research_links' => $product->researchLinks->map(fn ($link) => [
                    'id' => $link->id,
                    'title' => $link->title,
                    'article_url' => $link->article_url,
                    'authors' => $link->authors,
                    'journal' => $link->journal,
                    'publication_year' => $link->publication_year,
                    'pubmed_id' => $link->pubmed_id,
                    'doi' => $link->doi,
                    'description' => $link->description,
                    'sort_order' => $link->sort_order,
                ])->values(),
                'completion_status' => $product->completion_status,
                'completion_percentage' => $product->completion_percentage,
                'completion_step' => $product->completion_step,
            ],
        ]);
    }

    public function step5(StoreProductStep5Request $request): JsonResponse
    {
        $product = $this->productService->handleStep5($request->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'completion_status' => $product->completion_status,
                'completion_percentage' => $product->completion_percentage,
                'completion_step' => $product->completion_step,
                'pricing_groups_count' => $product->pricing->count(),
                'pricing_options_count' => $product->pricing->sum(fn ($pricing) => $pricing->options->count()),
            ],
        ]);
    }

    public function getStep5(string $productId): JsonResponse
    {
        $product = $this->productService->getStep5Data($productId);

        $subscription = $product->pricing->firstWhere('pricing_type', 'subscription');
        $oneTime = $product->pricing->firstWhere('pricing_type', 'one_time');

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'pricing' => [
                    'subscription' => $subscription ? [
                        'id' => $subscription->id,
                        'title' => $subscription->title,
                        'description' => $subscription->description,
                        'is_active' => $subscription->is_active,
                        'options' => $subscription->options->map(fn ($option) => [
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
                        ])->values(),
                    ] : null,
                    'one_time' => $oneTime ? [
                        'id' => $oneTime->id,
                        'title' => $oneTime->title,
                        'description' => $oneTime->description,
                        'is_active' => $oneTime->is_active,
                        'options' => $oneTime->options->map(fn ($option) => [
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
                        ])->values(),
                    ] : null,
                ],
                'completion_status' => $product->completion_status,
                'completion_percentage' => $product->completion_percentage,
                'completion_step' => $product->completion_step,
            ],
        ]);
    }

    public function status(string $productId): JsonResponse
    {
        $product = $this->productService->getStepStatus($productId);

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'completion_status' => $product->completion_status,
                'completion_percentage' => $product->completion_percentage,
                'current_step' => $product->completion_step,
                'steps' => [
                    [
                        'step' => 1,
                        'name' => 'basic_info',
                        'is_completed' => $product->completion_step > 1,
                    ],
                    [
                        'step' => 2,
                        'name' => 'benefits_faqs',
                        'is_completed' => $product->completion_step > 2,
                    ],
                    [
                        'step' => 3,
                        'name' => 'treatment_content',
                        'is_completed' => $product->completion_step > 3,
                    ],
                    [
                        'step' => 4,
                        'name' => 'clinical_research',
                        'is_completed' => $product->completion_step > 4,
                    ],
                    [
                        'step' => 5,
                        'name' => 'pricing',
                        'is_completed' => $product->completion_step > 5 || $product->completion_percentage === 100,
                    ],
                ],
            ],
        ]);
    }
}
