<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_uuid' => ['required', 'uuid', 'exists:orders,order_uuid'],
            'coupon_code' => ['required', 'string', 'max:100'],
        ];
    }
}
