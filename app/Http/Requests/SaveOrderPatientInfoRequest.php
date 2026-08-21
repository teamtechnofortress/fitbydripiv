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
            'lastName' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'dateOfBirth' => ['required', 'date'],
            'phone' => ['required', 'string', 'max:30'],
            'gender' => ['required', 'string', 'max:50'],
            'heightFeet' => ['required', 'integer', 'min:1', 'max:8'],
            'heightInches' => ['required', 'integer', 'min:0', 'max:11'],
            'weight' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
