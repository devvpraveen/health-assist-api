<?php

namespace App\Http\Requests\Api\V1\Wellness;

use App\Models\WellnessContent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateWellnessContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var WellnessContent $content */
        $content = $this->route('content');

        return $this->user()?->can('update', $content) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'integer', Rule::exists('wellness_categories', 'id')],
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'summary' => ['sometimes', 'string'],
            'body' => ['sometimes', 'string'],
            'locale' => ['sometimes', 'string', 'max:10'],
            'is_clinical_advice' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in(WellnessContent::STATUSES)],
            'personalization_tags' => ['sometimes', 'nullable', 'array'],
            'personalization_tags.*' => ['string', 'max:100'],
            'published_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('is_clinical_advice') && config('wellness.force_non_clinical', true)) {
                $validator->errors()->add(
                    'is_clinical_advice',
                    'Phase 11 wellness content cannot be marked as clinical advice.'
                );
            }
        });
    }
}
