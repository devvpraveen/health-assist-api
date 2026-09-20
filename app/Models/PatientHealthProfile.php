<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PatientHealthProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $patient_id
 * @property string|null $medical_history
 * @property array<int, mixed>|null $conditions
 * @property array<int, mixed>|null $allergies
 * @property array<int, mixed>|null $medications
 * @property string|null $previous_treatments
 * @property array<int, mixed>|null $surgeries
 * @property string|null $family_history
 * @property string|null $lifestyle
 * @property string|null $emergency_information
 */
#[Fillable([
    'tenant_id',
    'patient_id',
    'medical_history',
    'conditions',
    'allergies',
    'medications',
    'previous_treatments',
    'surgeries',
    'family_history',
    'lifestyle',
    'emergency_information',
])]
class PatientHealthProfile extends Model
{
    /** @use HasFactory<PatientHealthProfileFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'allergies' => 'array',
            'medications' => 'array',
            'surgeries' => 'array',
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
