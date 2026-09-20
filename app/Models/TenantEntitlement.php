<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'key',
    'enabled',
    'limit_value',
    'period',
    'source',
    'meta',
])]
class TenantEntitlement extends Model
{
    use BelongsToTenant;

    protected $attributes = [
        'enabled' => true,
        'source' => 'package',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'limit_value' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
