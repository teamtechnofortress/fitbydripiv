<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveOrderPatientInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:255'],
            'middleName' => ['nullable', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'dateOfBirth' => ['required', 'date'],
            'age' => ['nullable', 'integer'],
            'gender' => ['nullable', 'string', 'max:50'],
            'ethnicity' => ['nullable', 'string', 'max:100'],
        ];
    }
}
