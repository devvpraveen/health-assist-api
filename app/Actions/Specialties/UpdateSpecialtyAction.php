<?php

namespace App\Actions\Specialties;

use App\Models\Specialty;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSpecialtyAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Specialty $specialty, array $data): Specialty
    {
        if ($specialty->tenant_id === null) {
            throw ValidationException::withMessages([
                'specialty' => ['System specialties cannot be modified.'],
            ]);
        }

        return DB::transaction(function () use ($specialty, $data): Specialty {
            $specialty->update($data);

            $this->auditLogger->log('specialty.updated', $specialty, [
                'specialty_uuid' => $specialty->uuid,
                'fields' => array_keys($data),
            ]);

            return $specialty->refresh();
        });
    }
}
