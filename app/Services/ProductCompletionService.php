<?php

namespace App\Services;

use App\Models\Product;

class ProductCompletionService
{
    public function update(Product $product): Product
    {
        $product->loadMissing(['images', 'benefits', 'faqs', 'researchLinks', 'pricing.options', 'ingredients']);

        $progress = 0;

        if ($this->hasStep1Data($product)) {
            $progress += 20;
        }

        if ($this->hasStep2Collections($product)) {
            $progress += 20;
        }

        if ($this->hasStep3Content($product)) {
            $progress += 20;
        }

        if ($this->hasStep4Research($product)) {
            $progress += 20;
        }

        if ($product->pricing->isNotEmpty() && $product->pricing->contains(fn ($pricing) => $pricing->options->isNotEmpty())) {
            $progress += 20;
        }

        $product->completion_percentage = $progress;
        $product->completion_status = $this->resolveStatus($progress);
        $product->completion_step = $this->detectStep($product);
        $product->save();

        return $product->fresh(['images', 'benefits', 'faqs', 'researchLinks', 'pricing.options', 'ingredients', 'coverImage']);
    }

    protected function hasStep1Data(Product $product): bool
    {
        return filled($product->name)
            && filled($product->description)
            && filled($product->category)
            && $product->images->isNotEmpty()
            && filled($product->cover_image_id);
    }

    protected function hasStep2Collections(Product $product): bool
    {
        return $product->benefits->isNotEmpty() && $product->faqs->isNotEmpty();
    }

    protected function hasStep3Content(Product $product): bool
    {
        return filled($product->about_treatment)
            && filled($product->how_it_works)
            && $product->ingredients->isNotEmpty()
            && filled($product->treatment_duration)
            && filled($product->usage_instructions);
    }

    protected function hasStep4Research(Product $product): bool
    {
        return filled($product->clinical_research_description) && $product->researchLinks->isNotEmpty();
    }

    protected function resolveStatus(int $progress): string
    {
        if ($progress === 0) {
            return 'draft';
        }

        if ($progress < 100) {
            return 'incomplete';
        }

        return 'complete';
    }

    protected function detectStep(Product $product): int
    {
        if (! $this->hasStep1Data($product)) {
            return 1;
        }

        if (! $this->hasStep2Collections($product)) {
            return 2;
        }

        if (! $this->hasStep3Content($product)) {
            return 3;
        }

        if (! $this->hasStep4Research($product)) {
            return 4;
        }

        if ($product->pricing->isEmpty() || ! $product->pricing->contains(fn ($pricing) => $pricing->options->isNotEmpty())) {
            return 5;
        }

        return 6;
    }
}
