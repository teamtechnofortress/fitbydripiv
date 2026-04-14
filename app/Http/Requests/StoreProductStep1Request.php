<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'category' => 'required|in:weight_loss,wellness,longevity',
            'description' => 'required|string',
            'cover_image_id' => 'nullable|uuid|exists:product_images,id',
            'images' => 'nullable|array',
            'images.*.image_url' => 'required|string|max:500',
            'images.*.image_type' => 'required|in:cover,gallery',
            'images.*.sort_order' => 'nullable|integer',
            'images.*.slot_position' => 'nullable|integer',
        ];
    }
}
