<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Provider;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Appointment::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        if ($user === null) {
            return;
        }

        $canManage = $user->isSuperAdmin() || $user->hasPermission('appointments.manage');

        // Patients can only book for their linked chart.
        if (! $canManage) {
            $patientId = Patient::query()->where('user_id', $user->id)->value('id');
            if ($patientId) {
                $this->merge(['patient_id' => $patientId]);
            }
        }

        if ($this->filled('provider_uuid') && ! $this->filled('provider_id')) {
            $provider = Provider::query()
                ->where('uuid', $this->string('provider_uuid')->toString())
                ->first();

            if ($provider) {
                $this->merge([
                    'provider_id' => $provider->id,
                    'clinic_id' => $this->input('clinic_id') ?? $provider->clinic_id,
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'patient_id' => [
                'required',
                'integer',
                Rule::exists('patients', 'id')->where('tenant_id', $tenantId),
            ],
            'provider_id' => [
                'required_without:provider_uuid',
                'integer',
                Rule::exists('providers', 'id')->where('tenant_id', $tenantId),
            ],
            'provider_uuid' => [
                'required_without:provider_id',
                'uuid',
                Rule::exists('providers', 'uuid')->where('tenant_id', $tenantId),
            ],
            'clinic_id' => [
                'nullable',
                'integer',
                Rule::exists('clinics', 'id')->where('tenant_id', $tenantId),
            ],
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('tenant_id', $tenantId),
            ],
            'service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where('tenant_id', $tenantId),
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'status' => ['sometimes', 'string', Rule::in([
                Appointment::STATUS_REQUESTED,
                Appointment::STATUS_CONFIRMED,
            ])],
            'reason' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
