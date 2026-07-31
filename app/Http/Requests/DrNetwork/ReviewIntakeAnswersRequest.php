<?php

namespace App\Http\Requests\DrNetwork;

use Illuminate\Foundation\Http\FormRequest;

class ReviewIntakeAnswersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'array'],
            'answers.*.question_id' => ['nullable', 'integer', 'exists:network_intake_questions,id'],
            'answers.*.question_key' => ['nullable', 'string', 'max:100'],
            'answers.*.answer_value' => ['present'],
        ];
    }
}
