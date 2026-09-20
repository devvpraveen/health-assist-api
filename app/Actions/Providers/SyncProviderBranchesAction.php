<?php

namespace App\Actions\Providers;

use App\Models\Branch;
use App\Models\Provider;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncProviderBranchesAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  list<int>  $branchIds
     */
    public function handle(Provider $provider, array $branchIds): Provider
    {
        return DB::transaction(function () use ($provider, $branchIds): Provider {
            $tenantId = TenantContext::id();

            $validIds = Branch::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $branchIds)
                ->pluck('id')
                ->all();

            if (count($validIds) !== count(array_unique($branchIds))) {
                throw ValidationException::withMessages([
                    'branch_ids' => ['One or more branches are invalid for this tenant.'],
                ]);
            }

            $provider->branches()->sync($validIds);

            $this->auditLogger->log('provider.branches_synced', $provider, [
                'provider_uuid' => $provider->uuid,
                'branch_ids' => $validIds,
            ]);

            return $provider->load('branches');
        });
    }
}
