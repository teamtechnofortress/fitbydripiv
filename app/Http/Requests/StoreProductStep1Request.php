<?php

namespace App\Http\Requests;

use App\enums\ProductImageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductStep1Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable|uuid|exists:products,id',
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($this->input('id'), 'id'),
            ],
            'category' => 'required|in:weight_loss,wellness,longevity',
            'description' => 'required|string',
            'cover_image_id' => 'nullable|uuid|exists:product_images,id',
            'images' => 'nullable|array',
            'images.*.image_url' => 'required|string|max:500',
            'images.*.image_type' => ['required', Rule::in(ProductImageType::values())],
            'images.*.sort_order' => 'nullable|integer',
            'images.*.duration_ms' => 'nullable|integer|min:' . ProductImageType::MIN_SLIDE_DURATION_MS . '|max:' . ProductImageType::MAX_SLIDE_DURATION_MS,
            'images.*.is_enabled' => 'nullable|boolean',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $images = $this->input('images', []);

            if (! is_array($images)) {
                return;
            }

            $enabledCounts = [];

            foreach ($images as $image) {
                if (! is_array($image)) {
                    continue;
                }

                $type = isset($image['image_type']) && is_string($image['image_type'])
                    ? ProductImageType::tryFrom($image['image_type'])
                    : null;

                if (! $type) {
                    continue;
                }

                $isEnabled = $type === ProductImageType::COVER
                    ? true
                    : filter_var($image['is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($isEnabled !== true) {
                    continue;
                }

                $enabledCounts[$type->value] = ($enabledCounts[$type->value] ?? 0) + 1;
            }

            foreach (ProductImageType::cases() as $type) {
                $enabledCount = $enabledCounts[$type->value] ?? 0;
                $maxEnabled = $type->maxImages();

                if ($enabledCount <= $maxEnabled) {
                    continue;
                }

                $validator->errors()->add(
                    'images',
                    "Only {$maxEnabled} enabled images are allowed for {$type->label()}."
                );
            }
        });
    }
}
