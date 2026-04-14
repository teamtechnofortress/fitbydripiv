<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductStep2Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|uuid|exists:products,id',
            'benefits' => 'nullable|array',
            'benefits.*.benefit_text' => 'required|string',
            'benefits.*.sort_order' => 'nullable|integer',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'required|string',
            'faqs.*.answer' => 'required|string',
            'faqs.*.sort_order' => 'nullable|integer',
        ];
    }
}
