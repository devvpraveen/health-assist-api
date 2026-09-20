<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PatientWellnessPreferenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $patient_id
 * @property list<string> $interests
 * @property list<string>|null $goals
 * @property list<string>|null $excluded_tags
 * @property bool $reminder_opt_in
 */
#[Fillable([
    'tenant_id',
    'patient_id',
    'interests',
    'goals',
    'excluded_tags',
    'reminder_opt_in',
])]
class PatientWellnessPreference extends Model
{
    /** @use HasFactory<PatientWellnessPreferenceFactory> */
    use BelongsToTenant, HasFactory;

    protected $attributes = [
        'reminder_opt_in' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'interests' => 'array',
            'goals' => 'array',
            'excluded_tags' => 'array',
            'reminder_opt_in' => 'boolean',
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
