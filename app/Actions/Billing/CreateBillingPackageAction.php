<?php

namespace App\Actions\Billing;

use App\Models\BillingPackage;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateBillingPackageAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): BillingPackage
    {
        return DB::transaction(function () use ($data): BillingPackage {
            $name = $data['name'];
            $slug = $data['slug'] ?? Str::slug($name);

            $package = BillingPackage::query()->create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => TenantContext::id(),
                'clinic_id' => $data['clinic_id'] ?? null,
                'name' => $name,
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'session_count' => $data['session_count'],
                'validity_days' => $data['validity_days'] ?? null,
                'price_cents' => $data['price_cents'],
                'currency' => $data['currency'] ?? config('billing.currency', 'INR'),
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->auditLogger->log('billing.package.created', $package, [
                'package_uuid' => $package->uuid,
                'slug' => $package->slug,
            ]);

            return $package;
        });
    }
}
