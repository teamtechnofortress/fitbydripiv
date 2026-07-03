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
            'provider_guid' => ['nullable', 'string', 'max:150'],
            'scheduled_time' => ['nullable', 'date'],
            'schedule_start_date' => ['nullable', 'date'],
            'schedule_end_date' => ['nullable', 'date'],
            'start_datetime' => ['nullable', 'date'],
            'end_datetime' => ['nullable', 'date'],
            'appt_length' => ['nullable', 'integer', 'min:1', 'max:240'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'slot_schedule_id' => ['nullable', 'string', 'max:150'],
        ];
    }
}
