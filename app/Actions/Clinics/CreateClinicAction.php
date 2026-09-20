<?php

namespace App\Actions\Clinics;

use App\Models\Clinic;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateClinicAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Clinic
    {
        return DB::transaction(function () use ($data): Clinic {
            if (empty($data['slug']) && ! empty($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $clinic = Clinic::query()->create([
                ...$data,
                'tenant_id' => TenantContext::id(),
            ]);

            $this->auditLogger->log('clinic.created', $clinic, [
                'clinic_uuid' => $clinic->uuid,
                'slug' => $clinic->slug,
            ]);

            return $clinic;
        });
    }
}
