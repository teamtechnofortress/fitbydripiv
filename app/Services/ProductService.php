<?php

namespace App\Services;

use App\Models\PricingOption;
use App\Models\ProductIngredientMap;
use App\Models\Product;
use App\Models\ProductBenefit;
use App\Models\ProductFaq;
use App\Models\ProductImage;
use App\Models\ProductPricing;
use App\Models\ProductResearchLink;
use App\Models\Ingredient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        protected ProductCompletionService $productCompletionService
    ) {
    }

    public function createProductBasicInfo(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            return Product::create($this->filterProductAttributes($data, [
                'name',
                'category',
                'description',
                'is_featured',
                'is_published',
                'completion_status',
                'completion_percentage',
                'completion_step',
                'cover_image_id',
            ]));
        });
    }

    public function handleStep1(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = ! empty($data['id'])
                ? Product::findOrFail($data['id'])
                : new Product();

            $product->fill($this->filterProductAttributes($data, [
                'name',
                'category',
                'description',
            ]));
            $product->is_published = false;
            $product->save();

            if (array_key_exists('images', $data)) {
                $coverImageId = $this->replaceProductImages($product, $data['images'] ?? []);

                if ($coverImageId !== null) {
                    $product->cover_image_id = $coverImageId;
                    $product->save();
                } elseif (! empty($data['cover_image_id'])) {
                    $product->cover_image_id = $this->resolveCoverImageId($product, $data['cover_image_id']);
                    $product->save();
                } elseif (empty($data['images'])) {
                    $product->cover_image_id = null;
                    $product->save();
                }
            } elseif (! empty($data['cover_image_id'])) {
                $product->cover_image_id = $this->resolveCoverImageId($product, $data['cover_image_id']);
                $product->save();
            }

            return $this->productCompletionService->update($product);
        });
    }

    public function handleStep2(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::findOrFail($data['product_id']);

            if (array_key_exists('benefits', $data)) {
                $this->replaceProductBenefits($product, $data['benefits'] ?? []);
            }

            if (array_key_exists('faqs', $data)) {
                $this->replaceProductFaqs($product, $data['faqs'] ?? []);
            }

            return $this->productCompletionService->update($product);
        });
    }

    public function handleStep3(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::findOrFail($data['product_id']);

            $product->fill($this->filterProductAttributes($data, [
                'about_treatment',
                'how_it_works',
                'treatment_duration',
                'usage_instructions',
            ]));
            $product->save();

            if (array_key_exists('ingredients', $data)) {
                $this->replaceProductIngredients($product, $data['ingredients'] ?? []);
            }

            return $this->productCompletionService->update($product);
        });
    }

    public function handleStep4(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::findOrFail($data['product_id']);

            $product->fill($this->filterProductAttributes($data, [
                'clinical_research_description',
            ]));
            $product->save();

            if (array_key_exists('research_links', $data)) {
                $this->replaceProductResearchLinks($product, $data['research_links'] ?? []);
            }

            return $this->productCompletionService->update($product);
        });
    }

    public function handleStep5(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::findOrFail($data['product_id']);

            $pricingLayers = $this->normalizeStep5Pricing($data['pricing']);
            $this->replaceProductPricing($product, $pricingLayers);

            return $this->productCompletionService->update($product);
        });
    }

    public function getStepStatus(string $productId): Product
    {
        $product = Product::findOrFail($productId);

        return $this->productCompletionService->update($product);
    }

    public function getStep1Data(string $productId): Product
    {
        return $this->productCompletionService->update(
            Product::with(['images', 'coverImage'])->findOrFail($productId)
        );
    }

    public function getStep2Data(string $productId): Product
    {
        return $this->productCompletionService->update(
            Product::with(['benefits', 'faqs'])->findOrFail($productId)
        );
    }

    public function getStep3Data(string $productId): Product
    {
        return $this->productCompletionService->update(
            Product::with(['ingredients'])->findOrFail($productId)
        );
    }

    public function getStep4Data(string $productId): Product
    {
        return $this->productCompletionService->update(
            Product::with(['researchLinks'])->findOrFail($productId)
        );
    }

    public function getStep5Data(string $productId): Product
    {
        return $this->productCompletionService->update(
            Product::with(['pricing.options'])->findOrFail($productId)
        );
    }

    public function updateProductContent(Product|string $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product = $this->resolveProduct($product);
            $product->fill($this->filterProductAttributes($data, [
                'name',
                'category',
                'description',
                'about_treatment',
                'how_it_works',
                'key_ingredients',
                'treatment_duration',
                'usage_instructions',
                'research_description',
                'clinical_research_description',
                'is_featured',
                'is_published',
                'completion_status',
                'completion_percentage',
                'completion_step',
                'cover_image_id',
            ]));
            $product->save();

            return $product->fresh();
        });
    }

    public function addProductImages(Product|string $product, array $images, ?string $coverImageId = null): Product
    {
        return DB::transaction(function () use ($product, $images, $coverImageId) {
            $product = $this->resolveProduct($product);

            $detectedCoverImageId = $this->replaceProductImages($product, $images);

            if ($coverImageId !== null) {
                $product->cover_image_id = $this->resolveCoverImageId($product, $coverImageId);
                $product->save();
            } elseif ($detectedCoverImageId !== null) {
                $product->cover_image_id = $detectedCoverImageId;
                $product->save();
            }

            return $product->fresh('images', 'coverImage');
        });
    }

    public function addProductBenefits(Product|string $product, array $benefits): Product
    {
        return DB::transaction(function () use ($product, $benefits) {
            $product = $this->resolveProduct($product);

            $this->replaceProductBenefits($product, $benefits);

            return $product->fresh('benefits');
        });
    }

    public function addProductFaqs(Product|string $product, array $faqs): Product
    {
        return DB::transaction(function () use ($product, $faqs) {
            $product = $this->resolveProduct($product);

            $this->replaceProductFaqs($product, $faqs);

            return $product->fresh('faqs');
        });
    }

    public function addProductResearchLinks(Product|string $product, array $researchLinks): Product
    {
        return DB::transaction(function () use ($product, $researchLinks) {
            $product = $this->resolveProduct($product);

            $this->replaceProductResearchLinks($product, $researchLinks);

            return $product->fresh('researchLinks');
        });
    }

    public function addProductPricing(Product|string $product, array $pricingLayers): Product
    {
        return DB::transaction(function () use ($product, $pricingLayers) {
            $product = $this->resolveProduct($product);

            $this->replaceProductPricing($product, $pricingLayers);

            return $product->fresh('pricing.options');
        });
    }

    protected function resolveProduct(Product|string $product): Product
    {
        if ($product instanceof Product) {
            return $product;
        }

        return Product::findOrFail($product);
    }

    protected function filterProductAttributes(array $data, array $allowedKeys): array
    {
        return Arr::only($data, $allowedKeys);
    }

    protected function replaceProductImages(Product $product, array $images): ?string
    {
        ProductImage::where('product_id', $product->id)->delete();

        $coverImageId = null;

        foreach (array_values($images) as $index => $image) {
            $createdImage = ProductImage::create([
                'product_id' => $product->id,
                'slot_position' => $image['slot_position'] ?? ($index + 1),
                'image_url' => $image['image_url'],
                'image_type' => $image['image_type'],
                'sort_order' => $image['sort_order'] ?? $index,
            ]);

            if (($image['image_type'] ?? null) === 'cover' && $coverImageId === null) {
                $coverImageId = $createdImage->id;
            }
        }

        return $coverImageId;
    }

    protected function resolveCoverImageId(Product $product, string $coverImageId): string
    {
        return ProductImage::where('product_id', $product->id)
            ->where('id', $coverImageId)
            ->valueOrFail('id');
    }

    protected function replaceProductBenefits(Product $product, array $benefits): void
    {
        ProductBenefit::where('product_id', $product->id)->delete();

        foreach (array_values($benefits) as $index => $benefit) {
            ProductBenefit::create([
                'product_id' => $product->id,
                'benefit_text' => is_array($benefit) ? $benefit['benefit_text'] : $benefit,
                'sort_order' => is_array($benefit) ? ($benefit['sort_order'] ?? $index) : $index,
            ]);
        }
    }

    protected function replaceProductFaqs(Product $product, array $faqs): void
    {
        ProductFaq::where('product_id', $product->id)->delete();

        foreach (array_values($faqs) as $index => $faq) {
            ProductFaq::create([
                'product_id' => $product->id,
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'sort_order' => $faq['sort_order'] ?? $index,
            ]);
        }
    }

    protected function replaceProductIngredients(Product $product, array $ingredients): void
    {
        ProductIngredientMap::where('product_id', $product->id)->delete();

        $ingredientNames = [];

        foreach (array_values($ingredients) as $index => $ingredient) {
            $ingredientModel = ! empty($ingredient['ingredient_id'])
                ? Ingredient::findOrFail($ingredient['ingredient_id'])
                : Ingredient::firstOrCreate(
                    ['name' => $ingredient['name']],
                    ['description' => $ingredient['description'] ?? null]
                );

            if (
                empty($ingredient['ingredient_id'])
                && ! empty($ingredient['description'])
                && empty($ingredientModel->description)
            ) {
                $ingredientModel->description = $ingredient['description'];
                $ingredientModel->save();
            }

            ProductIngredientMap::create([
                'product_id' => $product->id,
                'ingredient_id' => $ingredientModel->id,
                'sort_order' => $ingredient['sort_order'] ?? $index,
            ]);

            $ingredientNames[] = $ingredientModel->name;
        }

        $product->key_ingredients = empty($ingredientNames) ? null : implode(', ', $ingredientNames);
        $product->save();
    }

    protected function replaceProductResearchLinks(Product $product, array $researchLinks): void
    {
        ProductResearchLink::where('product_id', $product->id)->delete();

        foreach (array_values($researchLinks) as $index => $researchLink) {
            ProductResearchLink::create([
                'product_id' => $product->id,
                'title' => $researchLink['title'],
                'article_url' => $researchLink['article_url'],
                'authors' => $researchLink['authors'] ?? null,
                'journal' => $researchLink['journal'] ?? null,
                'publication_year' => $researchLink['publication_year'] ?? null,
                'pubmed_id' => $researchLink['pubmed_id'] ?? null,
                'doi' => $researchLink['doi'] ?? null,
                'description' => $researchLink['description'] ?? null,
                'sort_order' => $researchLink['sort_order'] ?? $index,
            ]);
        }
    }

    protected function replaceProductPricing(Product $product, array $pricingLayers): void
    {
        $pricingIds = ProductPricing::where('product_id', $product->id)->pluck('id');
        if ($pricingIds->isNotEmpty()) {
            PricingOption::whereIn('pricing_id', $pricingIds)->delete();
        }
        ProductPricing::where('product_id', $product->id)->delete();

        foreach (array_values($pricingLayers) as $pricingLayer) {
            $pricing = ProductPricing::create([
                'product_id' => $product->id,
                'pricing_type' => $pricingLayer['pricing_type'],
                'title' => $pricingLayer['title'],
                'description' => $pricingLayer['description'] ?? null,
                'is_active' => $pricingLayer['is_active'] ?? true,
            ]);

            foreach (array_values($pricingLayer['options'] ?? []) as $optionIndex => $option) {
                PricingOption::create([
                    'pricing_id' => $pricing->id,
                    'billing_interval' => $option['billing_interval'],
                    'interval_count' => $option['interval_count'] ?? 1,
                    'price' => $option['price'],
                    'discount_percent' => $option['discount_percent'] ?? null,
                    'final_price' => $option['final_price'],
                    'label' => $option['label'],
                    'sort_order' => $option['sort_order'] ?? $optionIndex,
                    'is_default' => $option['is_default'] ?? false,
                    'metadata' => $option['metadata'] ?? null,
                ]);
            }
        }
    }

    protected function normalizeStep5Pricing(array $pricing): array
    {
        $normalized = [];

        foreach (['subscription', 'one_time'] as $pricingType) {
            if (empty($pricing[$pricingType])) {
                continue;
            }

            $normalized[] = [
                'pricing_type' => $pricingType,
                'title' => $pricing[$pricingType]['title'],
                'description' => $pricing[$pricingType]['description'] ?? null,
                'is_active' => $pricing[$pricingType]['is_active'] ?? true,
                'options' => array_values($pricing[$pricingType]['options'] ?? []),
            ];
        }

        return $normalized;
    }
}
