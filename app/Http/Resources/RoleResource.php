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
            'is_locked' => $this->slug === 'super_admin',
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
