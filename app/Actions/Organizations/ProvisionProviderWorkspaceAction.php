<?php

namespace App\Actions\Organizations;

use App\Actions\Modules\AssignPackageToTenantAction;
use App\Actions\Providers\EnsureLinkedProviderAction;
use App\Actions\Roles\AssignRoleAction;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\Package;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProvisionProviderWorkspaceAction
{
    public const ORG_TYPES = [
        'doctor' => ['label' => 'Doctor / Individual Provider', 'role' => 'provider', 'persona' => 'provider'],
        'clinic' => ['label' => 'Clinic', 'role' => 'clinic_admin', 'persona' => 'clinic'],
        'hospital' => ['label' => 'Hospital', 'role' => 'clinic_admin', 'persona' => 'clinic'],
        'diagnostic' => ['label' => 'Diagnostic Center', 'role' => 'clinic_admin', 'persona' => 'clinic'],
        'physiotherapy' => ['label' => 'Physiotherapy Center', 'role' => 'clinic_admin', 'persona' => 'clinic'],
        'pharmacy' => ['label' => 'Pharmacy', 'role' => 'clinic_admin', 'persona' => 'clinic'],
        'other' => ['label' => 'Other', 'role' => 'clinic_admin', 'persona' => 'clinic'],
    ];

    public function __construct(
        private AssignRoleAction $assignRoleAction,
        private AssignPackageToTenantAction $assignPackageToTenantAction,
        private EnsureLinkedProviderAction $ensureLinkedProviderAction,
    ) {}

    /**
     * @param  array{
     *   org_type: string,
     *   name?: string|null,
     *   organization_name?: string|null,
     *   phone?: string|null,
     *   city?: string|null,
     *   package_key?: string|null
     * }  $data
     * @return array{user: User, tenant: Tenant, organization: Organization, persona: string, role_slug: string}
     */
    public function handle(User $user, array $data): array
    {
        $orgType = strtolower(trim((string) ($data['org_type'] ?? '')));
        if (! isset(self::ORG_TYPES[$orgType])) {
            throw ValidationException::withMessages([
                'org_type' => ['Choose a valid organization type.'],
            ]);
        }

        $config = self::ORG_TYPES[$orgType];
        $displayName = trim((string) ($data['name'] ?? $user->name));
        $orgName = trim((string) ($data['organization_name'] ?? ''));
        if ($orgName === '') {
            $orgName = $orgType === 'doctor'
                ? ($displayName !== '' && $displayName !== 'Patient' ? $displayName : 'My Practice')
                : ($displayName !== '' && $displayName !== 'Patient' ? $displayName.' Care' : 'My Clinic');
        }

        $phone = trim((string) ($data['phone'] ?? $user->phone ?? ''));
        $city = trim((string) ($data['city'] ?? ''));
        $packageKey = $data['package_key'] ?? 'free';

        return DB::transaction(function () use ($user, $orgType, $config, $displayName, $orgName, $phone, $city, $packageKey): array {
            if ($displayName !== '' && $displayName !== 'Patient') {
                $user->forceFill(['name' => $displayName])->save();
            }
            if ($phone !== '' && empty($user->phone)) {
                $user->forceFill(['phone' => $phone])->save();
            }

            $tenant = $this->resolveOrCreateTenant($user, $orgName);
            TenantContext::set($tenant->id);

            $user->forceFill(['tenant_id' => $tenant->id])->save();

            $organization = Organization::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->orderBy('id')
                ->first();

            if ($organization === null) {
                $organization = Organization::query()->create([
                    'tenant_id' => $tenant->id,
                    'name' => $orgName,
                    'slug' => Str::slug($orgName).'-'.Str::lower(Str::random(4)),
                    'status' => 'active',
                    'phone' => $phone !== '' ? $phone : null,
                    'city' => $city !== '' ? $city : null,
                    'description' => null,
                    'meta' => [
                        'org_type' => $orgType,
                        'onboarding_completed' => true,
                        'getting_started_dismissed' => false,
                        'public_sections' => $this->defaultPublicSections(),
                    ],
                ]);
            } else {
                $meta = $organization->meta ?? [];
                $meta['org_type'] = $orgType;
                $meta['onboarding_completed'] = true;
                $meta['public_sections'] ??= $this->defaultPublicSections();
                $organization->forceFill([
                    'name' => $orgName,
                    'phone' => $phone !== '' ? $phone : $organization->phone,
                    'city' => $city !== '' ? $city : $organization->city,
                    'meta' => $meta,
                ])->save();
            }

            $clinic = Clinic::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->orderBy('id')
                ->first();

            if ($clinic === null) {
                Clinic::query()->create([
                    'tenant_id' => $tenant->id,
                    'organization_id' => $organization->id,
                    'name' => $orgName,
                    'slug' => Str::slug($orgName).'-'.Str::lower(Str::random(4)),
                    'type' => $this->clinicTypeForOrgType($orgType),
                    'phone' => $phone !== '' ? $phone : null,
                    'city' => $city !== '' ? $city : null,
                    'is_public' => false,
                    'status' => 'active',
                    'default_locale' => 'en',
                    'meta' => [
                        'public_sections' => $this->defaultPublicSections(),
                    ],
                ]);
            }

            $role = Role::query()->whereNull('tenant_id')->where('slug', $config['role'])->first();
            if ($role === null) {
                throw ValidationException::withMessages([
                    'org_type' => ['Account type is not available yet. Run role seeders.'],
                ]);
            }

            if (! $user->hasRole($config['role'])) {
                $this->assignRoleAction->handle($user, $role, $tenant->id, null, $user);
            }

            if ($config['role'] === 'provider') {
                $this->ensureLinkedProviderAction->handle($user->fresh());
            }

            $package = Package::query()->where('key', $packageKey)->first()
                ?? Package::query()->where('key', 'starter')->first()
                ?? Package::query()->where('key', 'free')->first();

            if ($package) {
                $this->assignPackageToTenantAction->handle($tenant, $package, true);
            }

            return [
                'user' => $user->fresh(['roles.permissions', 'tenant']),
                'tenant' => $tenant->fresh(),
                'organization' => $organization->fresh(),
                'persona' => $config['persona'],
                'role_slug' => $config['role'],
            ];
        });
    }

    private function resolveOrCreateTenant(User $user, string $orgName): Tenant
    {
        // Existing dedicated workspace (not the shared demo discovery tenant) — reuse.
        if ($user->tenant_id) {
            $existing = Tenant::query()->find($user->tenant_id);
            if ($existing && $existing->slug !== 'healthassist-demo' && $existing->slug !== 'healthassist-platform') {
                return $existing;
            }
        }

        $base = Str::slug($orgName) ?: 'workspace';
        $slug = $base.'-'.Str::lower(Str::random(6));

        return Tenant::query()->create([
            'name' => $orgName,
            'slug' => $slug,
            'status' => 'active',
            'plan_code' => 'free',
        ]);
    }

    private function clinicTypeForOrgType(string $orgType): string
    {
        return match ($orgType) {
            'hospital' => 'hospital',
            'diagnostic' => 'diagnostic',
            'physiotherapy' => 'wellness',
            default => 'clinic',
        };
    }

    /**
     * @return list<array{key: string, label: string, visible: bool, order: int}>
     */
    private function defaultPublicSections(): array
    {
        $sections = [
            'about',
            'services',
            'doctors',
            'working_hours',
            'appointments',
            'facilities',
            'videos',
            'reviews',
            'contact',
        ];

        return collect($sections)->values()->map(fn (string $key, int $i) => [
            'key' => $key,
            'label' => Str::headline($key),
            'visible' => true,
            'order' => $i + 1,
        ])->all();
    }
}
