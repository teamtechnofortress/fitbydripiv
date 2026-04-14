<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProductStep5Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|uuid|exists:products,id',

            'pricing' => 'required|array',

            'pricing.subscription' => 'nullable|array',
            'pricing.subscription.title' => 'required_with:pricing.subscription|string|max:255',
            'pricing.subscription.description' => 'nullable|string',
            'pricing.subscription.is_active' => 'nullable|boolean',
            'pricing.subscription.options' => 'nullable|array',
            'pricing.subscription.options.*.label' => 'required|string|max:255',
            'pricing.subscription.options.*.billing_interval' => 'required|in:day,week,month,year',
            'pricing.subscription.options.*.interval_count' => 'nullable|integer|min:1',
            'pricing.subscription.options.*.price' => 'required|numeric|min:0',
            'pricing.subscription.options.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'pricing.subscription.options.*.final_price' => 'required|numeric|min:0',
            'pricing.subscription.options.*.sort_order' => 'nullable|integer',
            'pricing.subscription.options.*.is_default' => 'nullable|boolean',
            'pricing.subscription.options.*.metadata' => 'nullable|array',

            'pricing.one_time' => 'nullable|array',
            'pricing.one_time.title' => 'required_with:pricing.one_time|string|max:255',
            'pricing.one_time.description' => 'nullable|string',
            'pricing.one_time.is_active' => 'nullable|boolean',
            'pricing.one_time.options' => 'nullable|array',
            'pricing.one_time.options.*.label' => 'required|string|max:255',
            'pricing.one_time.options.*.billing_interval' => 'required|in:one_time',
            'pricing.one_time.options.*.interval_count' => 'nullable|integer|min:1',
            'pricing.one_time.options.*.price' => 'required|numeric|min:0',
            'pricing.one_time.options.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'pricing.one_time.options.*.final_price' => 'required|numeric|min:0',
            'pricing.one_time.options.*.sort_order' => 'nullable|integer',
            'pricing.one_time.options.*.is_default' => 'nullable|boolean',
            'pricing.one_time.options.*.metadata' => 'nullable|array',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $pricing = $this->input('pricing', []);

            if (empty($pricing['subscription']) && empty($pricing['one_time'])) {
                $validator->errors()->add('pricing', 'At least one pricing group is required.');
            }
        });
    }
}
