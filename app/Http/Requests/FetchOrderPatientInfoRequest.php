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
            'dateOfBirth' => ['required', 'date'],
            'email' => ['required_without_all:phone,cell', 'nullable', 'email', 'max:255'],
            'phone' => ['required_without_all:email,cell', 'nullable', 'string', 'max:30'],
            'cell' => ['required_without_all:email,phone', 'nullable', 'string', 'max:30'],
        ];
    }
}
