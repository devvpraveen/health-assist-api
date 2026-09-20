<?php

namespace App\Http\Requests\Api\V1\Medications;

use App\Models\Medication;
use App\Models\Patient;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return $this->user()?->can('create', [Medication::class, $patient]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'provider_id' => ['nullable', 'integer', Rule::exists('providers', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:255'],
            'dosage' => ['required', 'string', 'max:255'],
            'frequency_label' => ['nullable', 'string', 'max:255'],
            'route' => ['nullable', 'string', 'max:100'],
            'instructions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'string', Rule::in(Medication::STATUSES)],
        ];
    }
}
