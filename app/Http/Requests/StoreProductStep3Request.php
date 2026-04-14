<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProductStep3Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|uuid|exists:products,id',
            'about_treatment' => 'required|string',
            'how_it_works' => 'required|string',
            'treatment_duration' => 'required|string',
            'usage_instructions' => 'required|string',
            'ingredients' => 'nullable|array',
            'ingredients.*.ingredient_id' => 'nullable|uuid|exists:ingredients,id',
            'ingredients.*.name' => 'nullable|string|max:255',
            'ingredients.*.description' => 'nullable|string',
            'ingredients.*.sort_order' => 'nullable|integer',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('ingredients', []) as $index => $ingredient) {
                $hasExisting = ! empty($ingredient['ingredient_id']);
                $hasNew = ! empty($ingredient['name']);

                if (! $hasExisting && ! $hasNew) {
                    $validator->errors()->add(
                        "ingredients.{$index}",
                        'Each ingredient item must include either ingredient_id or name.'
                    );
                }
            }
        });
    }
}
