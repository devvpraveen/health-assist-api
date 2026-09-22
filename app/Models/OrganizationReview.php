<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'tenant_id',
    'clinic_id',
    'patient_id',
    'rating',
    'title',
    'body',
    'author_name',
    'status',
    'response',
    'responded_at',
])]
class OrganizationReview extends Model
{
    use BelongsToTenant;

    protected $attributes = [
        'status' => 'pending',
        'rating' => 5,
    ];

    protected static function booted(): void
    {
        static::creating(function (OrganizationReview $row): void {
            if (empty($row->uuid)) {
                $row->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'responded_at' => 'datetime',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
