<?php

namespace App\Support\Content\Sections\Handlers;

use App\Models\CmsCategory;
use App\Models\PageSection;
use App\Models\Product;

class HeroSection
{
    public static function handle(PageSection $section, array $context = []): array
    {
        $content = is_array($section->content) ? $section->content : [];
        $source = $content['source'] ?? 'static';

        $headline = $content['headline'] ?? null;
        $description = $content['description'] ?? null;
        $background = static::normalizeBackground($content);
        $cta = $content['cta'] ?? null;

        if ($source === 'category' && ($context['category'] ?? null) instanceof CmsCategory) {
            $category = $context['category'];

            $headline = $headline ?? $category->name;
            $description = $description ?? $category->description;
            $background = static::resolveCategoryBackground($category, $background);
        }

        if ($source === 'product' && ($context['product'] ?? null) instanceof Product) {
            $product = $context['product'];

            $headline = $headline ?? $product->name;
            $description = $description ?? $product->description;
            $background = static::resolveProductBackground($product, $background);
        }

        if (! empty($content['override']) && is_array($content['override'])) {
            $override = $content['override'];

            $headline = $override['headline'] ?? $headline;
            $description = $override['description'] ?? $description;
            $background = static::applyBackgroundOverride($background, $override['background'] ?? null);
            $cta = $override['cta'] ?? $cta;
        }

        return [
            'id' => $section->id,
            'section_key' => $section->section_key,
            'type' => $section->type?->value ?? $section->getRawOriginal('type'),
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'headline' => $headline,
            'description' => $description,
            'background' => $background,
            'cta' => $cta,
            'content' => $content,
            'sort_order' => $section->sort_order,
        ];
    }

    protected static function normalizeBackground(array $content): mixed
    {
        if (isset($content['background'])) {
            return $content['background'];
        }

        if (! empty($content['video_url'])) {
            return [
                'type' => 'video',
                'url' => $content['video_url'],
            ];
        }

        if (! empty($content['image_url'])) {
            return [
                'type' => 'image',
                'url' => $content['image_url'],
            ];
        }

        return null;
    }

    protected static function resolveCategoryBackground(CmsCategory $category, mixed $background): mixed
    {
        if ($category->background_video) {
            return [
                'type' => 'video',
                'url' => $category->background_video,
                'playback_speed' => $category->video_playback_speed,
            ];
        }

        if ($category->landscape_banner) {
            return [
                'type' => 'image',
                'url' => $category->landscape_banner,
            ];
        }

        if ($category->banner_image) {
            return [
                'type' => 'image',
                'url' => $category->banner_image,
            ];
        }

        return $background;
    }

    protected static function resolveProductBackground(Product $product, mixed $background): mixed
    {
        if ($product->relationLoaded('coverImage') && $product->coverImage) {
            return [
                'type' => 'image',
                'url' => $product->coverImage->image_url,
            ];
        }

        return $background;
    }

    protected static function applyBackgroundOverride(mixed $background, mixed $override): mixed
    {
        if ($override === null) {
            return $background;
        }

        return $override;
    }
}
