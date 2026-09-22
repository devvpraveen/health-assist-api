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
    'created_by_user_id',
    'title',
    'body',
    'published_at',
    'is_public',
    'status',
])]
class ClinicNotice extends Model
{
    use BelongsToTenant;

    protected $attributes = [
        'status' => 'draft',
        'is_public' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicNotice $row): void {
            if (empty($row->uuid)) {
                $row->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_public' => 'boolean',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
