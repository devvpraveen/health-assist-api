<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'last_login_at' => $this->last_login_at,
            'email_verified_at' => $this->email_verified_at,
            'is_super_admin' => $this->isSuperAdmin(),
            'is_platform_admin' => $this->isPlatformAdmin(),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
            ])),
            'permissions' => $this->when(
                $this->relationLoaded('roles'),
                function () {
                    if ($this->isSuperAdmin()) {
                        return ['*'];
                    }

                    return $this->roles
                        ->flatMap(function ($role) {
                            if ($role->relationLoaded('permissions')) {
                                return $role->permissions->pluck('slug');
                            }

                            return $role->permissions()->pluck('permissions.slug');
                        })
                        ->unique()
                        ->values()
                        ->all();
                },
            ),
            'tenant' => new TenantResource($this->whenLoaded('tenant')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
