<?php

namespace App\Actions\Providers;

use App\Models\Provider;
use App\Models\Specialty;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncProviderSpecialtiesAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  list<array{id: int, is_primary?: bool}|int>  $specialties
     */
    public function handle(Provider $provider, array $specialties): Provider
    {
        return DB::transaction(function () use ($provider, $specialties): Provider {
            $tenantId = TenantContext::id();
            $sync = [];

            foreach ($specialties as $item) {
                if (is_int($item) || (is_string($item) && ctype_digit($item))) {
                    $id = (int) $item;
                    $sync[$id] = ['is_primary' => false];

                    continue;
                }

                if (! is_array($item) || ! isset($item['id'])) {
                    throw ValidationException::withMessages([
                        'specialty_ids' => ['Each specialty must include an id.'],
                    ]);
                }

                $sync[(int) $item['id']] = [
                    'is_primary' => (bool) ($item['is_primary'] ?? false),
                ];
            }

            $ids = array_keys($sync);

            $validCount = Specialty::query()
                ->visibleToTenant($tenantId)
                ->whereIn('id', $ids)
                ->count();

            if ($validCount !== count($ids)) {
                throw ValidationException::withMessages([
                    'specialty_ids' => ['One or more specialties are invalid for this tenant.'],
                ]);
            }

            $provider->specialties()->sync($sync);

            $this->auditLogger->log('provider.specialties_synced', $provider, [
                'provider_uuid' => $provider->uuid,
                'specialty_ids' => $ids,
            ]);

            return $provider->load('specialties');
        });
    }
}
