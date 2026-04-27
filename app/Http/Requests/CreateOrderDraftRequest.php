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
            'product_slug' => ['required', 'string', 'exists:products,slug'],
            'pricing_type' => ['required', Rule::in(Order::PRICING_TYPES)],
            'pricing_option_id' => ['required', 'uuid', 'exists:pricing_options,id'],
        ];
    }
}
