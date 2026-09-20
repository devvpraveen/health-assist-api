<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ClinicalDischargeSummary;
use App\Models\Patient;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClinicalDischargeSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return $this->user()?->can('create', [ClinicalDischargeSummary::class, $patient]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'provider_id' => ['nullable', 'integer', Rule::exists('providers', 'id')->where('tenant_id', $tenantId)],
            'clinic_id' => ['nullable', 'integer', Rule::exists('clinics', 'id')->where('tenant_id', $tenantId)],
            'treatment_plan_id' => ['nullable', 'integer', Rule::exists('clinical_treatment_plans', 'id')->where('tenant_id', $tenantId)],
            'discharged_at' => ['required', 'date'],
            'reason' => ['nullable', 'string'],
            'initial_condition' => ['nullable', 'string'],
            'treatment_provided' => ['nullable', 'string'],
            'progress_summary' => ['nullable', 'string'],
            'current_status' => ['nullable', 'string'],
            'home_program' => ['nullable', 'string'],
            'follow_up' => ['nullable', 'string'],
            'referral' => ['nullable', 'string'],
            'source' => ['sometimes', 'string', Rule::in(ClinicalDocumentWorkflow::SOURCES)],
        ];
    }
}
