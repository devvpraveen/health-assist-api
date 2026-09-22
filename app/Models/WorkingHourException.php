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
    'date',
    'is_closed',
    'opens_at',
    'closes_at',
    'reason',
])]
class WorkingHourException extends Model
{
    use BelongsToTenant;

    protected $attributes = [
        'is_closed' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (WorkingHourException $row): void {
            if (empty($row->uuid)) {
                $row->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_closed' => 'boolean',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
