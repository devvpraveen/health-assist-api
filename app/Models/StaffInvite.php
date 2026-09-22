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
    'invited_by_user_id',
    'email',
    'name',
    'role_slug',
    'designation',
    'token',
    'status',
    'accepted_at',
    'expires_at',
])]
class StaffInvite extends Model
{
    use BelongsToTenant;

    protected $attributes = [
        'status' => 'pending',
        'role_slug' => 'provider',
    ];

    protected static function booted(): void
    {
        static::creating(function (StaffInvite $row): void {
            if (empty($row->uuid)) {
                $row->uuid = (string) Str::uuid();
            }
            if (empty($row->token)) {
                $row->token = Str::random(40);
            }
            if (empty($row->expires_at)) {
                $row->expires_at = now()->addDays(14);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }
}
