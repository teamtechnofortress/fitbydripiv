<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrderDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_slug' => ['required', 'string', 'exists:cms_products,slug'],
            'pricing_type' => ['required', Rule::in(Order::PRICING_TYPES)],
            'subscription_discount_id' => ['nullable', 'string', 'exists:cms_subscription_discounts,id'],
        ];
    }
}
