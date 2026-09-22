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
    'provider_id',
    'day_of_week',
    'opens_at',
    'closes_at',
    'break_starts_at',
    'break_ends_at',
    'shift_index',
    'is_closed',
    'label',
])]
class WorkingHour extends Model
{
    use BelongsToTenant;

    protected $attributes = [
        'shift_index' => 1,
        'is_closed' => false,
    ];

    protected static function booted(): void
    {
        static::creating(function (WorkingHour $row): void {
            if (empty($row->uuid)) {
                $row->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'shift_index' => 'integer',
            'is_closed' => 'boolean',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
