<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ConvertDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'document' => [
                'required',
                'file',
                'mimes:docx',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'document.required' => 'Please select a DOCX document.',
            'document.file' => 'The uploaded document is invalid.',
            'document.mimes' => 'Only DOCX documents are supported.',
            'document.max' => 'The document may not be larger than 10 MB.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::warning('Document import validation failed.', [
            'errors' => $validator->errors()->toArray(),
            'method' => $this->method(),
            'path' => $this->path(),
            'content_type' => $this->headers->get('content-type'),
            'content_length' => $this->headers->get('content-length'),
            'has_document_file' => $this->hasFile('document'),
            'document_file' => $this->uploadedFileContext($this->file('document')),
            'uploaded_file_keys' => array_keys($this->allFiles()),
            'input_keys' => array_keys($this->except(array_keys($this->allFiles()))),
            'ip' => $this->ip(),
            'user_id' => $this->user()?->id,
        ]);

        parent::failedValidation($validator);
    }

    private function uploadedFileContext(mixed $file): ?array
    {
        if (! $file instanceof UploadedFile) {
            return null;
        }

        return [
            'client_original_name' => $file->getClientOriginalName(),
            'client_mime_type' => $file->getClientMimeType(),
            'detected_mime_type' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'size_bytes' => $file->getSize(),
            'is_valid' => $file->isValid(),
            'error' => $file->getError(),
            'error_message' => $file->getErrorMessage(),
        ];
    }
}
