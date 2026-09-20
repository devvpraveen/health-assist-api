<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Exercise;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Exercise::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();
        $tenantKey = $tenantId === null ? 'system' : (string) $tenantId;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('exercises', 'slug')->where('tenant_key', $tenantKey)],
            'category' => ['nullable', 'string', 'max:100'],
            'instructions' => ['nullable', 'string'],
            'contraindications' => ['nullable', 'string'],
            'default_duration_seconds' => ['nullable', 'integer', 'min:1'],
            'default_sets' => ['nullable', 'integer', 'min:1'],
            'default_reps' => ['nullable', 'integer', 'min:1'],
            'difficulty' => ['nullable', 'string', 'max:50'],
            'media_url' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'string', Rule::in(Exercise::STATUSES)],
        ];
    }
}
