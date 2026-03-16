<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'pricing_type' => ['required', 'in:base,micro,sample'],
            'subscription_discount_id' => ['nullable', 'uuid'],
        ];
    }
}
