<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ClinicalExercisePlan;
use App\Models\Patient;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClinicalExercisePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return $this->user()?->can('create', [ClinicalExercisePlan::class, $patient]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'treatment_plan_id' => ['nullable', 'integer', Rule::exists('clinical_treatment_plans', 'id')->where('tenant_id', $tenantId)],
            'provider_id' => ['nullable', 'integer', Rule::exists('providers', 'id')->where('tenant_id', $tenantId)],
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'string', Rule::in(ClinicalExercisePlan::STATUSES)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
