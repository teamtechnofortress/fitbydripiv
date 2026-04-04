<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patient,id'],
            'product_slug' => ['required', 'string'],
            'pricing_type' => ['required', Rule::in(Order::PRICING_TYPES)],
            'subscription_discount_id' => ['nullable', 'uuid'],
        ];
    }
}
