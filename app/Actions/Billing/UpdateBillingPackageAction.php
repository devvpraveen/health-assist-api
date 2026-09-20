<?php

namespace App\Actions\Billing;

use App\Models\BillingPackage;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateBillingPackageAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(BillingPackage $package, array $data): BillingPackage
    {
        return DB::transaction(function () use ($package, $data): BillingPackage {
            if (isset($data['name']) && empty($data['slug']) && ! array_key_exists('slug', $data)) {
                $data['slug'] = Str::slug($data['name']);
            }

            $package->fill(collect($data)->only([
                'clinic_id',
                'name',
                'slug',
                'description',
                'session_count',
                'validity_days',
                'price_cents',
                'currency',
                'is_active',
            ])->all())->save();

            $this->auditLogger->log('billing.package.updated', $package, [
                'package_uuid' => $package->uuid,
            ]);

            return $package->fresh();
        });
    }
}
