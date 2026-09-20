<?php

namespace App\Http\Requests\Api\V1\Seo;

use App\Models\SeoEntity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeoEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SeoEntity $entity */
        $entity = $this->route('entity');

        return $this->user()?->can('update', $entity) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', Rule::in(config('seo.entity_types'))],
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'summary' => ['sometimes', 'string'],
            'body' => ['nullable', 'string'],
            'locale' => ['sometimes', 'string', 'max:16'],
            'parent_entity_id' => ['nullable', 'integer', 'exists:seo_entities,id'],
            'status' => ['sometimes', 'string', Rule::in(config('seo.entity_statuses'))],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'canonical_path' => ['nullable', 'string', 'max:255'],
            'schema_type' => ['nullable', 'string', 'max:255'],
            'structured_facts' => ['nullable', 'array'],
            'structured_facts.*' => ['string'],
            'direct_answer' => ['nullable', 'string'],
            'citations' => ['nullable', 'array'],
            'reviewer_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'last_reviewed_at' => ['nullable', 'date'],
        ];
    }
}
