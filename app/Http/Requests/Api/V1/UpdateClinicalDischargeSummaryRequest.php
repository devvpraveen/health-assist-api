<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ClinicalDischargeSummary;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClinicalDischargeSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ClinicalDischargeSummary $dischargeSummary */
        $dischargeSummary = $this->route('discharge_summary');

        return $this->user()?->can('update', $dischargeSummary) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'provider_id' => ['sometimes', 'nullable', 'integer', Rule::exists('providers', 'id')->where('tenant_id', $tenantId)],
            'clinic_id' => ['sometimes', 'nullable', 'integer', Rule::exists('clinics', 'id')->where('tenant_id', $tenantId)],
            'treatment_plan_id' => ['sometimes', 'nullable', 'integer', Rule::exists('clinical_treatment_plans', 'id')->where('tenant_id', $tenantId)],
            'discharged_at' => ['sometimes', 'date'],
            'reason' => ['sometimes', 'nullable', 'string'],
            'initial_condition' => ['sometimes', 'nullable', 'string'],
            'treatment_provided' => ['sometimes', 'nullable', 'string'],
            'progress_summary' => ['sometimes', 'nullable', 'string'],
            'current_status' => ['sometimes', 'nullable', 'string'],
            'home_program' => ['sometimes', 'nullable', 'string'],
            'follow_up' => ['sometimes', 'nullable', 'string'],
            'referral' => ['sometimes', 'nullable', 'string'],
            'source' => ['sometimes', 'string', Rule::in(ClinicalDocumentWorkflow::SOURCES)],
        ];
    }
}
