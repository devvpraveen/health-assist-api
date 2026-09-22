<?php

namespace App\Http\Resources;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => null,
            'name' => $this->name,
            'slug' => $this->slug,
            'tenant_id' => $this->tenant_id,
            'is_system' => $this->tenant_id === null,
            'is_platform_admin' => $this->tenant_id === null
                && ($this->slug === 'super_admin' || str_starts_with((string) $this->slug, 'platform_')),
            'is_tenant_template' => $this->tenant_id === null
                && in_array($this->slug, [
                    'organization_admin',
                    'branch_manager',
                    'clinic_admin',
                    'provider',
                    'patient',
                ], true),
            'is_locked' => $this->slug === 'super_admin',
            'scope' => $this->tenant_id !== null
                ? 'tenant'
                : (($this->slug === 'super_admin' || str_starts_with((string) $this->slug, 'platform_'))
                    ? 'platform'
                    : 'template'),
            'permission_slugs' => $this->whenLoaded(
                'permissions',
                fn () => $this->permissions->pluck('slug')->values()->all(),
            ),
            'permissions_count' => $this->when(
                isset($this->permissions_count) || $this->relationLoaded('permissions'),
                fn () => $this->permissions_count ?? $this->permissions->count(),
            ),
            'users_count' => $this->when(
                isset($this->users_count),
                fn () => $this->users_count,
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
