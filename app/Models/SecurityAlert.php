<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $severity
 * @property string $title
 * @property string|null $detail
 * @property string $status
 */
#[Fillable(['uuid', 'severity', 'title', 'detail', 'status', 'resolved_at'])]
class SecurityAlert extends Model
{
    public const SEVERITIES = ['low', 'medium', 'high', 'critical'];

    public const STATUSES = ['open', 'acknowledged', 'resolved'];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SecurityAlert $alert): void {
            if (empty($alert->uuid)) {
                $alert->uuid = (string) Str::uuid();
            }
        });
    }
}
