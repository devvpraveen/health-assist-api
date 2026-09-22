<?php

namespace App\Actions\Modules;

use App\Models\Package;
use App\Models\PackageEntitlement;
use App\Models\PackageLimit;
use App\Models\PlatformModule;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateSaaSPackageAction
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{
     *   key: string,
     *   name: string,
     *   description?: string|null,
     *   clone_from?: string|null,
     *   module_keys?: list<string>,
     *   price_monthly?: int|float|null,
     *   price_cents?: int|null,
     *   currency?: string|null,
     *   validity_days?: int|null,
     *   sort_order?: int|null,
     *   is_active?: bool|null,
     *   limits?: array<string, int|null>,
     * }  $data
     */
    public function handle(array $data): Package
    {
        $key = Str::slug((string) $data['key'], '_');
        if ($key === '') {
            throw ValidationException::withMessages(['key' => ['A valid package key is required.']]);
        }

        if (Package::query()->where('key', $key)->exists()) {
            throw ValidationException::withMessages(['key' => ['That package key already exists.']]);
        }

        return DB::transaction(function () use ($data, $key): Package {
            $clone = null;
            if (! empty($data['clone_from'])) {
                $clone = Package::query()
                    ->with(['modules', 'entitlements', 'limits'])
                    ->where('key', $data['clone_from'])
                    ->first();
                if ($clone === null) {
                    throw ValidationException::withMessages([
                        'clone_from' => ['Template package not found.'],
                    ]);
                }
            }

            $metadata = $clone?->metadata ?? [];
            if (array_key_exists('price_monthly', $data) && $data['price_monthly'] !== null) {
                $metadata['price_monthly'] = (int) $data['price_monthly'];
            }

            $priceCents = array_key_exists('price_cents', $data) && $data['price_cents'] !== null
                ? (int) $data['price_cents']
                : (array_key_exists('price_monthly', $data) && $data['price_monthly'] !== null
                    ? ((int) $data['price_monthly']) * 100
                    : (int) ($clone?->price_cents ?? 0));

            if (! isset($metadata['price_monthly']) && $priceCents > 0) {
                $metadata['price_monthly'] = (int) floor($priceCents / 100);
            }

            $package = Package::query()->create([
                'key' => $key,
                'name' => (string) $data['name'],
                'slug' => $key,
                'description' => $data['description'] ?? $clone?->description,
                'sort_order' => (int) ($data['sort_order'] ?? (($clone?->sort_order ?? 0) + 10)),
                'is_active' => $data['is_active'] ?? true,
                'price_cents' => $priceCents,
                'currency' => strtoupper((string) ($data['currency'] ?? $clone?->currency ?? 'INR')),
                'validity_days' => array_key_exists('validity_days', $data)
                    ? ($data['validity_days'] !== null ? (int) $data['validity_days'] : null)
                    : $clone?->validity_days,
                'metadata' => $metadata ?: null,
            ]);

            if ($clone) {
                $sync = [];
                foreach ($clone->modules as $module) {
                    $sync[$module->id] = [
                        'inclusion' => $module->pivot->inclusion ?? 'included',
                    ];
                }
                $package->modules()->sync($sync);

                foreach ($clone->entitlements as $entitlement) {
                    PackageEntitlement::query()->create([
                        'package_id' => $package->id,
                        'key' => $entitlement->key,
                        'enabled' => $entitlement->enabled,
                        'meta' => $entitlement->meta,
                    ]);
                }

                foreach ($clone->limits as $limit) {
                    PackageLimit::query()->create([
                        'package_id' => $package->id,
                        'key' => $limit->key,
                        'value' => $limit->value,
                        'period' => $limit->period,
                        'meta' => $limit->meta,
                    ]);
                }
            } else {
                $moduleKeys = $data['module_keys'] ?? ['core', 'patients', 'providers', 'appointments'];
                $modules = PlatformModule::query()->whereIn('key', $moduleKeys)->get();
                $sync = [];
                foreach ($modules as $module) {
                    $sync[$module->id] = ['inclusion' => 'included'];
                }
                $package->modules()->sync($sync);

                foreach (['ai_assistant' => false, 'whatsapp' => false, 'custom_branding' => false] as $entKey => $enabled) {
                    PackageEntitlement::query()->create([
                        'package_id' => $package->id,
                        'key' => $entKey,
                        'enabled' => $enabled,
                    ]);
                }

                $limits = $data['limits'] ?? [
                    'patients' => 100,
                    'staff' => 5,
                    'branches' => 1,
                    'ai_messages_month' => 500,
                    'storage_gb' => 5,
                ];
                foreach ($limits as $limitKey => $value) {
                    PackageLimit::query()->create([
                        'package_id' => $package->id,
                        'key' => $limitKey,
                        'value' => $value,
                        'period' => 'month',
                    ]);
                }
            }

            // Optional limit overrides after clone/create.
            if (! empty($data['limits']) && is_array($data['limits'])) {
                foreach ($data['limits'] as $limitKey => $value) {
                    PackageLimit::query()->updateOrCreate(
                        ['package_id' => $package->id, 'key' => $limitKey],
                        ['value' => $value, 'period' => 'month'],
                    );
                }
            }

            $this->auditLogger->log('packages.created', $package, [
                'package_key' => $package->key,
                'clone_from' => $data['clone_from'] ?? null,
            ]);

            return $package->load(['modules', 'entitlements', 'limits']);
        });
    }
}
