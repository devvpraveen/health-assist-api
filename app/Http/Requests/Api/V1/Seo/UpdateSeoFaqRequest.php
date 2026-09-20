<?php

namespace App\Http\Requests\Api\V1\Seo;

use App\Models\SeoFaq;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeoFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SeoFaq $faq */
        $faq = $this->route('faq');

        return $this->user()?->can('update', $faq) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entity_id' => ['nullable', 'integer', 'exists:seo_entities,id'],
            'question' => ['sometimes', 'string', 'max:500'],
            'answer' => ['sometimes', 'string'],
            'locale' => ['sometimes', 'string', 'max:16'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', Rule::in([SeoFaq::STATUS_DRAFT, SeoFaq::STATUS_PUBLISHED])],
        ];
    }
}
