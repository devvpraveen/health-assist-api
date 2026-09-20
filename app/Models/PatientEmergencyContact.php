<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PatientEmergencyContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $patient_id
 * @property string $name
 * @property string $relationship
 * @property string $phone
 * @property string|null $email
 * @property bool $is_primary
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'patient_id',
    'name',
    'relationship',
    'phone',
    'email',
    'is_primary',
])]
class PatientEmergencyContact extends Model
{
    /** @use HasFactory<PatientEmergencyContactFactory> */
    use BelongsToTenant, HasFactory;

    protected $attributes = [
        'is_primary' => false,
    ];

    protected static function booted(): void
    {
        static::creating(function (PatientEmergencyContact $contact): void {
            if (empty($contact->uuid)) {
                $contact->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
