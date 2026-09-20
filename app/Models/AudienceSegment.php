<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\AudienceSegmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property string $key
 * @property string $name
 * @property array<string, mixed>|null $definition
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'key',
    'name',
    'definition',
])]
class AudienceSegment extends Model
{
    /** @use HasFactory<AudienceSegmentFactory> */
    use BelongsToTenant, HasFactory;

    protected static function booted(): void
    {
        static::creating(function (AudienceSegment $segment): void {
            if (empty($segment->uuid)) {
                $segment->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'definition' => 'array',
        ];
    }
}
