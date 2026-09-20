<?php

namespace App\Actions\Appointments;

use App\Models\AppointmentWaitlistEntry;
use App\Models\Clinic;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateWaitlistEntryAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): AppointmentWaitlistEntry
    {
        return DB::transaction(function () use ($data): AppointmentWaitlistEntry {
            $patient = Patient::query()->findOrFail($data['patient_id']);
            $clinic = Clinic::query()->findOrFail($data['clinic_id']);

            if ($patient->tenant_id !== TenantContext::id() || $clinic->tenant_id !== TenantContext::id()) {
                throw ValidationException::withMessages([
                    'patient_id' => ['Patient and clinic must belong to the current tenant.'],
                ]);
            }

            $entry = AppointmentWaitlistEntry::query()->create([
                'tenant_id' => TenantContext::id(),
                'patient_id' => $patient->id,
                'provider_id' => $data['provider_id'] ?? null,
                'clinic_id' => $clinic->id,
                'service_id' => $data['service_id'] ?? null,
                'preferred_date' => $data['preferred_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => AppointmentWaitlistEntry::STATUS_PENDING,
            ]);

            $this->auditLogger->log('appointment_waitlist.created', $entry, [
                'waitlist_uuid' => $entry->uuid,
                'patient_id' => $patient->id,
            ]);

            return $entry->fresh(['patient', 'clinic', 'provider', 'service']);
        });
    }
}
