<?php

namespace App\Services\Mobile;

use App\Models\MobileUserPreference;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Capability-based mobile experience builder.
 * Modules are derived from permissions (and linked patient profile), not role equality alone.
 */
class MobileExperienceBuilder
{
    public const ACCOUNT_TYPES = [
        'patient',
        'healthcare_professional',
        'clinic_staff',
        'clinic_owner',
    ];

    public const PROFESSIONAL_TYPES = [
        'doctor',
        'physiotherapist',
        'nurse',
        'dietitian',
        'psychologist',
        'radiologist',
        'other',
    ];

    /**
     * @return array<string, mixed>
     */
    public function build(User $user, ?MobileUserPreference $preference = null): array
    {
        $user->loadMissing(['roles.permissions', 'tenant']);

        $permissions = $this->permissionSlugs($user);
        $roles = $user->roles->pluck('slug')->values()->all();
        $preference ??= MobileUserPreference::query()->where('user_id', $user->id)->first();

        $organization = Organization::query()
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('id')
            ->first();

        $linkedPatient = Patient::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->first();

        $accountTypes = $this->deriveAccountTypes($roles, $permissions, $linkedPatient !== null);
        $activeAccountType = $preference?->account_type;
        if ($activeAccountType !== null && ! in_array($activeAccountType, self::ACCOUNT_TYPES, true)) {
            $activeAccountType = null;
        }

        $modules = $this->mapModules($permissions, $linkedPatient !== null, $activeAccountType);
        $modules = $this->filterModulesByTenant($modules, $user->tenant_id);
        $entitlements = $this->mapEntitlements($permissions, $user->tenant_id);
        $workspaces = $this->buildWorkspaces($accountTypes, $organization, $linkedPatient !== null);
        $featureFlags = [
            'workspace_switcher' => count($workspaces) > 1,
        ];

        $activeWorkspace = $preference?->active_workspace;
        if ($activeWorkspace !== null) {
            $workspaceIds = array_column($workspaces, 'id');
            if (! in_array($activeWorkspace, $workspaceIds, true)) {
                $activeWorkspace = null;
            }
        }

        return [
            'user' => [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'account_types' => $accountTypes,
            'active_account_type' => $activeAccountType,
            'professional_type' => $preference?->professional_type,
            'active_workspace' => $activeWorkspace,
            'organization' => $organization === null ? null : [
                'uuid' => $organization->uuid,
                'name' => $organization->name,
            ],
            'roles' => $roles,
            'permissions' => $permissions->values()->all(),
            'modules' => $modules,
            'entitlements' => $entitlements,
            'feature_flags' => $featureFlags,
            'workspaces' => $workspaces,
            'has_linked_patient' => $linkedPatient !== null,
        ];
    }

    /**
     * @param  list<string>  $roles
     * @param  Collection<int, string>  $permissions
     * @return list<string>
     */
    public function deriveAccountTypes(array $roles, Collection $permissions, bool $hasLinkedPatient): array
    {
        $types = [];

        if ($hasLinkedPatient || in_array('patient', $roles, true)) {
            $types[] = 'patient';
        }

        if (
            $permissions->contains(fn (string $p): bool => str_starts_with($p, 'clinical.'))
            || $permissions->contains('providers.manage')
            || in_array('provider', $roles, true)
        ) {
            $types[] = 'healthcare_professional';
        }

        if (
            in_array('branch_manager', $roles, true)
            || ($permissions->contains('appointments.manage') && $permissions->contains('patients.view') && ! in_array('organization_admin', $roles, true))
        ) {
            $types[] = 'clinic_staff';
        }

        if (
            in_array('organization_admin', $roles, true)
            || $permissions->contains('organizations.manage')
            || $permissions->contains('billing.invoices.manage')
        ) {
            $types[] = 'clinic_owner';
        }

        // Org admins can still choose patient/professional during onboarding.
        if ($permissions->isNotEmpty() && $types === []) {
            $types[] = 'clinic_staff';
        }

        return array_values(array_unique($types));
    }

    /**
     * @param  Collection<int, string>  $permissions
     * @return list<string>
     */
    public function mapModules(Collection $permissions, bool $hasLinkedPatient, ?string $activeAccountType = null): array
    {
        $modules = ['profile', 'notifications'];

        $add = function (string $module) use (&$modules): void {
            if (! in_array($module, $modules, true)) {
                $modules[] = $module;
            }
        };

        if ($permissions->contains('patients.view') || $permissions->contains('patients.manage')) {
            $add('patients');
            $add('dashboard');
        }

        if ($permissions->contains(fn (string $p): bool => str_starts_with($p, 'appointments.'))) {
            $add('appointments');
            $add('dashboard');
        }

        if ($permissions->contains(fn (string $p): bool => str_starts_with($p, 'clinical.notes'))) {
            $add('clinical_notes');
            $add('soap_notes');
        }

        if ($permissions->contains(fn (string $p): bool => str_starts_with($p, 'clinical.treatment'))) {
            $add('treatment_plans');
            $add('clinical_notes');
        }

        if ($permissions->contains(fn (string $p): bool => str_starts_with($p, 'clinical.exercises'))) {
            $add('exercise_plans');
            $add('clinical_notes');
        }

        if ($permissions->contains(fn (string $p): bool => str_starts_with($p, 'clinical.'))) {
            $add('clinical_notes');
            $add('dashboard');
        }

        if (
            $permissions->contains(fn (string $p): bool => str_starts_with($p, 'ai.'))
            || $permissions->contains(fn (string $p): bool => str_starts_with($p, 'health_guide.'))
        ) {
            $add('ai_assistant');
        }

        if ($permissions->contains(fn (string $p): bool => str_starts_with($p, 'billing.'))) {
            $add('billing');
            $add('dashboard');
        }

        if ($permissions->contains(fn (string $p): bool => str_starts_with($p, 'marketing.'))) {
            $add('marketing');
        }

        if ($permissions->contains('crm.manage')) {
            $add('crm');
        }

        if ($permissions->contains(fn (string $p): bool => str_starts_with($p, 'reports.'))) {
            $add('reports');
        }

        if ($permissions->contains('organizations.manage') || $permissions->contains('branches.manage')) {
            $add('settings');
            $add('dashboard');
        }

        if ($permissions->contains('whatsapp.view') || $permissions->contains('whatsapp.manage')) {
            $add('messages');
        }

        if ($hasLinkedPatient || $activeAccountType === 'patient') {
            foreach (['home', 'ai_assistant', 'appointments', 'providers', 'records', 'reports', 'treatment', 'messages', 'profile', 'medications'] as $patientModule) {
                $add($patientModule);
            }
        }

        sort($modules);

        return $modules;
    }

    /**
     * @param  list<string>  $modules
     * @return list<string>
     */
    public function filterModulesByTenant(array $modules, ?int $tenantId): array
    {
        if ($tenantId === null) {
            return $modules;
        }

        $activeKeys = app(\App\Actions\Modules\ResolveTenantModulesAction::class)->handle($tenantId);
        if ($activeKeys === []) {
            // Platform not provisioned for this tenant yet — keep permission-derived modules.
            return $modules;
        }

        $navMap = app(\App\Services\Modules\ModuleDefinitionRegistry::class)->navigationToModules();
        $always = ['profile', 'notifications'];

        return array_values(array_filter($modules, function (string $navKey) use ($activeKeys, $navMap, $always): bool {
            if (in_array($navKey, $always, true)) {
                return true;
            }
            $owners = $navMap[$navKey] ?? null;
            if ($owners === null || $owners === []) {
                return true;
            }

            foreach ($owners as $moduleKey) {
                if (in_array($moduleKey, $activeKeys, true)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * @param  Collection<int, string>  $permissions
     * @return array<string, bool|int|null>
     */
    public function mapEntitlements(Collection $permissions, ?int $tenantId = null): array
    {
        $base = [
            'ai_assistant' => $permissions->contains('ai.run')
                || $permissions->contains('health_guide.run')
                || $permissions->contains('ai.view'),
            'advanced_reports' => $permissions->contains('reports.analyze')
                || $permissions->contains('reports.review'),
        ];

        if ($tenantId === null) {
            return $base;
        }

        $resolved = app(\App\Actions\Modules\ResolveEntitlementsAction::class)->handle($tenantId);
        foreach ($resolved as $key => $row) {
            if (array_key_exists('limit_value', $row) && $row['limit_value'] !== null) {
                $base[$key] = $row['limit_value'];
            } else {
                $base[$key] = (bool) $row['enabled'];
            }
        }

        // Module presence can force entitlement off even if permission exists.
        $active = app(\App\Actions\Modules\ResolveTenantModulesAction::class)->handle($tenantId);
        if ($active !== []) {
            if (! in_array('health_guide', $active, true) && ! in_array('ai', $active, true)) {
                $base['ai_assistant'] = false;
            }
            if (! in_array('reports', $active, true)) {
                $base['advanced_reports'] = false;
            }
            $base['whatsapp'] = in_array('whatsapp', $active, true)
                && (($resolved['whatsapp']['enabled'] ?? true) === true);
        }

        return $base;
    }

    /**
     * @param  list<string>  $accountTypes
     * @return list<array{id: string, label: string, account_type: string, organization_uuid: string|null}>
     */
    public function buildWorkspaces(array $accountTypes, ?Organization $organization, bool $hasLinkedPatient): array
    {
        $workspaces = [];

        if ($organization !== null) {
            $orgType = in_array('clinic_owner', $accountTypes, true)
                ? 'clinic_owner'
                : (in_array('clinic_staff', $accountTypes, true) ? 'clinic_staff' : 'healthcare_professional');

            if (
                in_array('clinic_owner', $accountTypes, true)
                || in_array('clinic_staff', $accountTypes, true)
                || in_array('healthcare_professional', $accountTypes, true)
            ) {
                $workspaces[] = [
                    'id' => 'org:'.$organization->uuid,
                    'label' => $organization->name,
                    'account_type' => $orgType,
                    'organization_uuid' => $organization->uuid,
                ];
            }
        }

        if ($hasLinkedPatient || in_array('patient', $accountTypes, true)) {
            $workspaces[] = [
                'id' => 'patient:personal',
                'label' => 'Personal (patient)',
                'account_type' => 'patient',
                'organization_uuid' => null,
            ];
        }

        return $workspaces;
    }

    /**
     * @return Collection<int, string>
     */
    public function permissionSlugs(User $user): Collection
    {
        if ($user->isSuperAdmin()) {
            return Permission::query()->orderBy('slug')->pluck('slug');
        }

        /** @var Collection<int, string> $slugs */
        $slugs = $user->roles
            ->flatMap(fn (Role $role) => $role->permissions->pluck('slug'))
            ->unique()
            ->sort()
            ->values();

        return $slugs;
    }
}
