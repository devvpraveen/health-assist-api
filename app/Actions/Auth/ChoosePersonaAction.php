<?php

namespace App\Actions\Auth;

use App\Actions\Organizations\ProvisionProviderWorkspaceAction;
use App\Actions\Patients\EnsureLinkedPatientAction;
use App\Actions\Roles\AssignRoleAction;
use App\Models\Role;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Validation\ValidationException;

class ChoosePersonaAction
{
    /** Legacy + org-type personas accepted by the API. */
    public const PERSONAS = [
        'patient' => 'patient',
        'provider' => 'provider', // alias → doctor org type
        'clinic' => 'clinic_admin', // alias → clinic org type
        'doctor' => 'provider',
        'hospital' => 'clinic_admin',
        'diagnostic' => 'clinic_admin',
        'physiotherapy' => 'clinic_admin',
        'pharmacy' => 'clinic_admin',
        'other' => 'clinic_admin',
    ];

    public const PERSONA_ROLE_SLUGS = ['patient', 'provider', 'clinic_admin'];

    public function __construct(
        private AssignRoleAction $assignRoleAction,
        private EnsureLinkedPatientAction $ensureLinkedPatientAction,
        private ProvisionProviderWorkspaceAction $provisionProviderWorkspaceAction,
    ) {}

    /**
     * @param  array{
     *   organization_name?: string|null,
     *   phone?: string|null,
     *   city?: string|null,
     *   name?: string|null,
     *   package_key?: string|null
     * }  $profile
     * @return array{user: User, persona: string, role_slug: string, org_type?: string}
     */
    public function handle(User $user, string $persona, array $profile = []): array
    {
        $persona = strtolower(trim($persona));
        if (! isset(self::PERSONAS[$persona])) {
            throw ValidationException::withMessages([
                'persona' => ['Choose patient or a provider organization type.'],
            ]);
        }

        if ($this->hasPersonaRole($user)) {
            throw ValidationException::withMessages([
                'persona' => ['Your account type is already set. Ask an admin to change it.'],
            ]);
        }

        if ($persona === 'patient') {
            $tenantId = $user->tenant_id ?? TenantContext::id();
            if ($tenantId === null) {
                throw ValidationException::withMessages([
                    'persona' => ['Account is missing a clinic/tenant context.'],
                ]);
            }

            $role = Role::query()->whereNull('tenant_id')->where('slug', 'patient')->first();
            if ($role === null) {
                throw ValidationException::withMessages([
                    'persona' => ['Account type is not available yet. Run role seeders.'],
                ]);
            }

            if (! empty($profile['name'])) {
                $user->forceFill(['name' => trim((string) $profile['name'])])->save();
            }

            $this->assignRoleAction->handle($user, $role, (int) $tenantId, null, $user);
            $this->ensureLinkedPatientAction->handle($user);

            return [
                'user' => $user->fresh(['roles.permissions', 'tenant']),
                'persona' => 'patient',
                'role_slug' => 'patient',
                'org_type' => 'patient',
            ];
        }

        $orgType = match ($persona) {
            'provider' => 'doctor',
            'clinic' => 'clinic',
            default => $persona,
        };

        $result = $this->provisionProviderWorkspaceAction->handle($user, [
            'org_type' => $orgType,
            'name' => $profile['name'] ?? null,
            'organization_name' => $profile['organization_name'] ?? null,
            'phone' => $profile['phone'] ?? null,
            'city' => $profile['city'] ?? null,
            'package_key' => $profile['package_key'] ?? 'free',
        ]);

        return [
            'user' => $result['user'],
            'persona' => $result['persona'],
            'role_slug' => $result['role_slug'],
            'org_type' => $orgType,
        ];
    }

    public function hasPersonaRole(User $user): bool
    {
        $user->loadMissing('roles');

        return $user->roles->contains(
            fn (Role $role): bool => in_array($role->slug, self::PERSONA_ROLE_SLUGS, true)
                || in_array($role->slug, ['super_admin', 'organization_admin', 'branch_manager'], true)
        );
    }
}
