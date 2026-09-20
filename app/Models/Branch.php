<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $organization_id
 * @property int|null $clinic_id
 * @property string $name
 * @property string|null $code
 * @property string $timezone
 * @property string $status
 */
#[Fillable(['uuid', 'tenant_id', 'organization_id', 'clinic_id', 'name', 'code', 'timezone', 'status'])]
class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use BelongsToTenant, HasFactory;

    protected $attributes = [
        'timezone' => 'UTC',
        'status' => 'active',
    ];

    protected static function booted(): void
    {
        static::creating(function (Branch $branch): void {
            if (empty($branch->uuid)) {
                $branch->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Clinic, $this>
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
