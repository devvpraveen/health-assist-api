<?php

namespace App\Actions\Clinics;

use App\Models\Clinic;
use App\Services\AuditLogger;
use App\Support\MarketingProfile;
use Illuminate\Support\Facades\DB;

class UpdateClinicAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Clinic $clinic, array $data): Clinic
    {
        return DB::transaction(function () use ($clinic, $data): Clinic {
            if (array_key_exists('profile', $data) && is_array($data['profile'])) {
                $data['meta'] = MarketingProfile::mergeIntoMeta(
                    $data['profile'],
                    is_array($data['meta'] ?? null) ? $data['meta'] : $clinic->meta,
                );
                unset($data['profile']);
            }

            $clinic->update($data);

            $this->auditLogger->log('clinic.updated', $clinic, [
                'clinic_uuid' => $clinic->uuid,
                'fields' => array_keys($data),
            ]);

            return $clinic->refresh();
        });
    }
}
