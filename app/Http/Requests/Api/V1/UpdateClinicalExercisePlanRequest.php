<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ClinicalExercisePlan;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClinicalExercisePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ClinicalExercisePlan $exercisePlan */
        $exercisePlan = $this->route('exercise_plan');

        return $this->user()?->can('update', $exercisePlan) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'treatment_plan_id' => ['sometimes', 'nullable', 'integer', Rule::exists('clinical_treatment_plans', 'id')->where('tenant_id', $tenantId)],
            'provider_id' => ['sometimes', 'nullable', 'integer', Rule::exists('providers', 'id')->where('tenant_id', $tenantId)],
            'title' => ['sometimes', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'string', Rule::in(ClinicalExercisePlan::STATUSES)],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
