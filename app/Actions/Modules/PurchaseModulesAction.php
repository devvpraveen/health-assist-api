<?php

namespace App\Actions\Modules;

use App\Models\PlatformModule;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Services\AuditLogger;
use App\Services\Modules\ModuleDependencyEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PurchaseModulesAction
{
    public function __construct(
        private ModuleDependencyEngine $dependencies,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * Purchase/activate one or more modules à-la-carte (outside a package bundle).
     *
     * @param  list<string>  $moduleKeys
     * @return array{
     *   modules: list<TenantModule>,
     *   total_cents: int,
     *   currency: string,
     *   line_items: list<array{key: string, name: string, price_cents: int, validity_days: int|null, expires_at: string|null}>
     * }
     */
    public function handle(Tenant $tenant, array $moduleKeys): array
    {
        $keys = array_values(array_unique(array_filter(array_map('strval', $moduleKeys))));
        if ($keys === []) {
            throw ValidationException::withMessages([
                'module_keys' => ['Select at least one module.'],
            ]);
        }

        return DB::transaction(function () use ($tenant, $keys): array {
            $modules = PlatformModule::query()->whereIn('key', $keys)->get()->keyBy('key');
            $missing = array_values(array_diff($keys, $modules->keys()->all()));
            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'module_keys' => ['Unknown modules: '.implode(', ', $missing)],
                ]);
            }

            $lineItems = [];
            $rows = [];
            $total = 0;
            $currency = 'INR';

            foreach ($keys as $key) {
                /** @var PlatformModule $module */
                $module = $modules->get($key);
                if ($key === 'core') {
                    continue;
                }
                if (! $module->is_purchasable) {
                    throw new RuntimeException("Module [{$key}] is not available for individual purchase.");
                }

                $this->dependencies->assertCanActivate($key, $tenant);

                $price = (int) ($module->price_cents ?? 0);
                $currency = (string) ($module->currency ?: 'INR');
                $validityDays = $module->validity_days !== null ? (int) $module->validity_days : null;
                $startsAt = now();
                $expiresAt = $validityDays !== null ? $startsAt->copy()->addDays($validityDays) : null;

                $row = TenantModule::query()->withoutGlobalScopes()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'module_id' => $module->id,
                    ],
                    [
                        'status' => TenantModule::STATUS_ACTIVE,
                        'source' => TenantModule::SOURCE_ADDON,
                        'paid_cents' => $price,
                        'currency' => $currency,
                        'activated_at' => $startsAt,
                        'starts_at' => $startsAt,
                        'expires_at' => $expiresAt,
                        'deactivated_at' => null,
                        'purchase_meta' => [
                            'mode' => 'module',
                            'purchased_at' => $startsAt->toIso8601String(),
                            'validity_days' => $validityDays,
                            // Payment gateway not required in this environment.
                            'payment_status' => 'recorded',
                        ],
                    ],
                );

                $total += $price;
                $lineItems[] = [
                    'key' => $module->key,
                    'name' => $module->name,
                    'price_cents' => $price,
                    'validity_days' => $validityDays,
                    'expires_at' => $expiresAt?->toIso8601String(),
                ];
                $rows[] = $row->load('module');
            }

            $this->auditLogger->log('modules.purchased', $tenant, [
                'module_keys' => $keys,
                'total_cents' => $total,
                'currency' => $currency,
            ]);

            return [
                'modules' => $rows,
                'total_cents' => $total,
                'currency' => $currency,
                'line_items' => $lineItems,
            ];
        });
    }
}
