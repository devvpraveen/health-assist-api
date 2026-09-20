<?php

namespace App\Http\Requests\Api\V1\Seo;

use App\Models\SeoEntity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeoEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SeoEntity::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(config('seo.entity_types'))],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'summary' => ['required', 'string'],
            'body' => ['nullable', 'string'],
            'locale' => ['sometimes', 'string', 'max:16'],
            'parent_entity_id' => ['nullable', 'integer', 'exists:seo_entities,id'],
            'status' => ['sometimes', 'string', Rule::in([
                SeoEntity::STATUS_DRAFT,
                SeoEntity::STATUS_IN_REVIEW,
                SeoEntity::STATUS_ARCHIVED,
            ])],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'canonical_path' => ['nullable', 'string', 'max:255'],
            'schema_type' => ['nullable', 'string', 'max:255'],
            'structured_facts' => ['nullable', 'array'],
            'structured_facts.*' => ['string'],
            'direct_answer' => ['nullable', 'string'],
            'citations' => ['nullable', 'array'],
            'tenant_id' => ['sometimes', 'nullable', 'integer', 'exists:tenants,id'],
        ];
    }
}
