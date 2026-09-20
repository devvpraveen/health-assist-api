<?php

namespace App\Actions\Clinics;

use App\Models\Clinic;
use App\Models\Specialty;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncClinicSpecialtiesAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  list<int>  $specialtyIds
     */
    public function handle(Clinic $clinic, array $specialtyIds): Clinic
    {
        return DB::transaction(function () use ($clinic, $specialtyIds): Clinic {
            $tenantId = TenantContext::id();

            $validIds = Specialty::query()
                ->visibleToTenant($tenantId)
                ->whereIn('id', $specialtyIds)
                ->pluck('id')
                ->all();

            if (count($validIds) !== count(array_unique($specialtyIds))) {
                throw ValidationException::withMessages([
                    'specialty_ids' => ['One or more specialties are invalid for this tenant.'],
                ]);
            }

            $clinic->specialties()->sync($validIds);

            $this->auditLogger->log('clinic.specialties_synced', $clinic, [
                'clinic_uuid' => $clinic->uuid,
                'specialty_ids' => $validIds,
            ]);

            return $clinic->load('specialties');
        });
    }
}
