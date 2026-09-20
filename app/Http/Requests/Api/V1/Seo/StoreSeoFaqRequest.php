<?php

namespace App\Http\Requests\Api\V1\Seo;

use App\Models\SeoFaq;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeoFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SeoFaq::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entity_id' => ['nullable', 'integer', 'exists:seo_entities,id'],
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string'],
            'locale' => ['sometimes', 'string', 'max:16'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', Rule::in([SeoFaq::STATUS_DRAFT, SeoFaq::STATUS_PUBLISHED])],
            'tenant_id' => ['sometimes', 'nullable', 'integer', 'exists:tenants,id'],
        ];
    }
}
