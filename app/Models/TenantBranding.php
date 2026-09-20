<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string|null $logo_path
 * @property array<string, mixed>|null $colors
 */
#[Fillable(['tenant_id', 'logo_path', 'colors'])]
class TenantBranding extends Model
{
    protected $table = 'tenant_branding';

    protected $casts = [
        'colors' => 'array',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
