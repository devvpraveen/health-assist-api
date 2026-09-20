<?php

namespace App\Actions\Wellness;

use App\Models\Patient;
use App\Models\PatientWellnessPreference;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class UpsertPatientWellnessPreferencesAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Patient $patient, array $data): PatientWellnessPreference
    {
        return DB::transaction(function () use ($patient, $data): PatientWellnessPreference {
            $prefs = PatientWellnessPreference::query()->updateOrCreate(
                ['patient_id' => $patient->id],
                [
                    'tenant_id' => TenantContext::id() ?? $patient->tenant_id,
                    'interests' => $data['interests'] ?? [],
                    'goals' => $data['goals'] ?? null,
                    'excluded_tags' => $data['excluded_tags'] ?? null,
                    'reminder_opt_in' => $data['reminder_opt_in'] ?? true,
                ],
            );

            $this->auditLogger->log('wellness.preferences.upserted', $prefs, [
                'patient_uuid' => $patient->uuid,
            ]);

            return $prefs;
        });
    }
}
