<?php

namespace App\Http\Requests\Api\V1\Wellness;

use App\Models\WellnessContent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWellnessContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', WellnessContent::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('wellness_categories', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'summary' => ['required', 'string'],
            'body' => ['required', 'string'],
            'locale' => ['sometimes', 'string', 'max:10'],
            'is_clinical_advice' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in(WellnessContent::STATUSES)],
            'personalization_tags' => ['nullable', 'array'],
            'personalization_tags.*' => ['string', 'max:100'],
            'tenant_id' => ['sometimes', 'nullable', 'integer'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('is_clinical_advice') && config('wellness.force_non_clinical', true)) {
                // Force false in action; surface clear validation for API clients.
                $validator->errors()->add(
                    'is_clinical_advice',
                    'Phase 11 wellness content cannot be marked as clinical advice.'
                );
            }
        });
    }
}
