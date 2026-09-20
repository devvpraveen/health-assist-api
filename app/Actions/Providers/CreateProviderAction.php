<?php

namespace App\Actions\Providers;

use App\Models\Provider;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateProviderAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Provider
    {
        return DB::transaction(function () use ($data): Provider {
            $provider = Provider::query()->create([
                ...$data,
                'tenant_id' => TenantContext::id(),
            ]);

            $this->auditLogger->log('provider.created', $provider, [
                'provider_uuid' => $provider->uuid,
                'clinic_id' => $provider->clinic_id,
            ]);

            return $provider;
        });
    }
}
