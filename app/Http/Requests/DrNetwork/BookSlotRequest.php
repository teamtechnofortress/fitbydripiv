<?php

namespace App\Http\Requests\DrNetwork;

use Illuminate\Foundation\Http\FormRequest;

class BookSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_id' => ['nullable', 'string', 'max:150'],
            'scheduled_time' => ['nullable', 'date'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'slot_schedule_id' => ['nullable', 'string', 'max:150'],
        ];
    }
}
