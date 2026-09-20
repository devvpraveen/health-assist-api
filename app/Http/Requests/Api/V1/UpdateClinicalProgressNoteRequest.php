<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ClinicalProgressNote;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClinicalProgressNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ClinicalProgressNote $progressNote */
        $progressNote = $this->route('progress_note');

        return $this->user()?->can('update', $progressNote) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'provider_id' => ['sometimes', 'nullable', 'integer', Rule::exists('providers', 'id')->where('tenant_id', $tenantId)],
            'treatment_plan_id' => ['sometimes', 'nullable', 'integer', Rule::exists('clinical_treatment_plans', 'id')->where('tenant_id', $tenantId)],
            'appointment_id' => ['sometimes', 'nullable', 'integer', Rule::exists('appointments', 'id')->where('tenant_id', $tenantId)],
            'noted_at' => ['sometimes', 'date'],
            'note' => ['sometimes', 'string'],
            'measurements' => ['sometimes', 'nullable', 'array'],
            'source' => ['sometimes', 'string', Rule::in(ClinicalDocumentWorkflow::SOURCES)],
        ];
    }
}
