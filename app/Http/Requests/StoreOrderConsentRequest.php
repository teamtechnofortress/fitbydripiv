<?php

namespace App\Http\Requests;

use App\Models\OrderConsent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderConsentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('accepted')) {
            $this->merge([
                'accepted' => $this->boolean('accepted'),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'consent_key' => ['required', 'string', 'max:100', Rule::in([OrderConsent::KEY_TELEHEALTH_TERMS_CONSENT])],
            'consent_title' => ['nullable', 'string', 'max:255'],
            'content_version' => ['required', 'string', 'max:100', Rule::in([OrderConsent::VERSION_FITBYSHOT_TERMS_CONSENT])],
            'content_hash' => ['required', 'string', 'max:128'],
            'accepted' => ['required', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
