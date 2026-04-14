<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductStep4Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|uuid|exists:products,id',
            'clinical_research_description' => 'required|string',
            'research_links' => 'nullable|array',
            'research_links.*.title' => 'required|string|max:255',
            'research_links.*.article_url' => 'required|string|max:500',
            'research_links.*.authors' => 'nullable|string',
            'research_links.*.journal' => 'nullable|string',
            'research_links.*.publication_year' => 'nullable|integer',
            'research_links.*.pubmed_id' => 'nullable|string',
            'research_links.*.doi' => 'nullable|string',
            'research_links.*.description' => 'nullable|string',
            'research_links.*.sort_order' => 'nullable|integer',
        ];
    }
}
