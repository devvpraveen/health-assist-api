<?php

namespace App\Actions\Specialties;

use App\Models\Specialty;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateSpecialtyAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Specialty
    {
        return DB::transaction(function () use ($data): Specialty {
            if (empty($data['slug']) && ! empty($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $specialty = Specialty::query()->create([
                ...$data,
                'tenant_id' => TenantContext::id(),
            ]);

            $this->auditLogger->log('specialty.created', $specialty, [
                'specialty_uuid' => $specialty->uuid,
                'slug' => $specialty->slug,
            ]);

            return $specialty;
        });
    }
}
