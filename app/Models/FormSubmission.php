<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $form_definition_id
 * @property int $form_version_id
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property array<string, mixed> $payload
 * @property array<string, mixed>|null $meta
 * @property int|null $submitted_by_user_id
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'form_definition_id',
    'form_version_id',
    'subject_type',
    'subject_id',
    'payload',
    'meta',
    'submitted_by_user_id',
])]
class FormSubmission extends Model
{
    use BelongsToTenant;

    protected static function booted(): void
    {
        static::creating(function (FormSubmission $submission): void {
            if (empty($submission->uuid)) {
                $submission->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<FormDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class, 'form_definition_id');
    }

    /**
     * @return BelongsTo<FormVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class, 'form_version_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
