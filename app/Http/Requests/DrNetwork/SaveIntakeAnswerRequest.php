<?php

namespace App\Http\Requests\DrNetwork;

use Illuminate\Foundation\Http\FormRequest;

class SaveIntakeAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_id' => ['required', 'integer', 'exists:network_intake_questions,id'],
            'answer_value' => ['nullable'],
        ];
    }
}
