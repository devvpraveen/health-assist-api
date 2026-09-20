<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = TenantContext::id();

            if ($tenantId !== null) {
                $builder->where(
                    $builder->getModel()->getTable().'.tenant_id',
                    $tenantId,
                );
            }
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('tenant_id') === null && TenantContext::id() !== null) {
                $model->setAttribute('tenant_id', TenantContext::id());
            }
        });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTenant(Builder $query, int|string $tenantId): Builder
    {
        return $query->withoutGlobalScope('tenant')
            ->where($query->getModel()->getTable().'.tenant_id', $tenantId);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
