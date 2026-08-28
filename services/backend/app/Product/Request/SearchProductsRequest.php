<?php

declare(strict_types=1);

namespace App\Product\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:200'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'price_min' => ['sometimes', 'integer', 'min:0'],
            'price_max' => ['sometimes', 'integer', 'min:0'],
            'attr' => ['sometimes', 'array'],
            'attr.*' => ['array'],
            'attr.*.*' => ['string', 'max:200'],
            'sort' => ['sometimes', Rule::in(['relevance', 'price_asc', 'price_desc'])],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
