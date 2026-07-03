<?php

namespace App\Http\Requests\DrNetwork;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:20480'],
        ];
    }
}
