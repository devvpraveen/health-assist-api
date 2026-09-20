<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property string $tenant_key
 * @property string $name
 * @property string $slug
 */
#[Fillable(['tenant_id', 'tenant_key', 'name', 'slug'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Role $role): void {
            $role->tenant_key = $role->tenant_id === null
                ? 'system'
                : (string) $role->tenant_id;
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['tenant_id', 'branch_id', 'branch_key'])
            ->withTimestamps();
    }
}
