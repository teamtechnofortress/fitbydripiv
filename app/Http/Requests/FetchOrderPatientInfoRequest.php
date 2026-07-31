<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FetchOrderPatientInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required_without:phone', 'nullable', 'email', 'max:255'],
            'phone' => ['required_without:email', 'nullable', 'string', 'max:30'],
        ];
    }
}
