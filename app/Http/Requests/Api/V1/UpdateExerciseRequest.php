<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Exercise;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Exercise $exercise */
        $exercise = $this->route('exercise');

        return $this->user()?->can('update', $exercise) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();
        $tenantKey = $tenantId === null ? 'system' : (string) $tenantId;
        /** @var Exercise $exercise */
        $exercise = $this->route('exercise');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('exercises', 'slug')->where('tenant_key', $tenantKey)->ignore($exercise->id)],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'instructions' => ['sometimes', 'nullable', 'string'],
            'contraindications' => ['sometimes', 'nullable', 'string'],
            'default_duration_seconds' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'default_sets' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'default_reps' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'difficulty' => ['sometimes', 'nullable', 'string', 'max:50'],
            'media_url' => ['sometimes', 'nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'string', Rule::in(Exercise::STATUSES)],
        ];
    }
}
