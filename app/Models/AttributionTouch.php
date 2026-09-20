<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\AttributionTouchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property string $anonymous_id
 * @property int|null $user_id
 * @property int|null $patient_id
 * @property string|null $campaign
 * @property string|null $source
 * @property string|null $medium
 * @property string|null $content
 * @property string|null $term
 * @property string|null $referrer
 * @property string|null $landing_path
 * @property Carbon $captured_at
 * @property array<string, mixed>|null $meta
 */
#[Fillable([
    'tenant_id',
    'anonymous_id',
    'user_id',
    'patient_id',
    'campaign',
    'source',
    'medium',
    'content',
    'term',
    'referrer',
    'landing_path',
    'captured_at',
    'meta',
])]
class AttributionTouch extends Model
{
    /** @use HasFactory<AttributionTouchFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
