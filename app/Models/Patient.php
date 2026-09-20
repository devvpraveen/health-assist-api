<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $user_id
 * @property string $first_name
 * @property string $last_name
 * @property Carbon|null $date_of_birth
 * @property string|null $gender
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address_line1
 * @property string|null $address_line2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string|null $country
 * @property string $preferred_language
 * @property array<string, mixed>|null $communication_preferences
 * @property string $consent_status
 * @property Carbon|null $consent_at
 * @property string|null $profile_photo_path
 * @property string $status
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'user_id',
    'first_name',
    'last_name',
    'date_of_birth',
    'gender',
    'phone',
    'email',
    'address_line1',
    'address_line2',
    'city',
    'state',
    'postal_code',
    'country',
    'preferred_language',
    'communication_preferences',
    'consent_status',
    'consent_at',
    'profile_photo_path',
    'status',
])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use BelongsToTenant, HasFactory;

    public const CONSENT_STATUSES = ['pending', 'granted', 'revoked', 'expired'];

    public const STATUSES = ['active', 'inactive', 'archived'];

    protected $attributes = [
        'preferred_language' => 'en',
        'consent_status' => 'pending',
        'status' => 'active',
    ];

    protected static function booted(): void
    {
        static::creating(function (Patient $patient): void {
            if (empty($patient->uuid)) {
                $patient->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'communication_preferences' => 'array',
            'consent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne<PatientHealthProfile, $this>
     */
    public function healthProfile(): HasOne
    {
        return $this->hasOne(PatientHealthProfile::class);
    }

    /**
     * @return HasMany<PatientEmergencyContact, $this>
     */
    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(PatientEmergencyContact::class);
    }

    /**
     * @return HasMany<HealthRecord, $this>
     */
    public function healthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class);
    }

    /**
     * @return HasMany<PatientDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(PatientDocument::class);
    }

    /**
     * @return HasMany<ReportAnalysis, $this>
     */
    public function reportAnalyses(): HasMany
    {
        return $this->hasMany(ReportAnalysis::class);
    }

    /**
     * @return HasMany<PatientTimelineEvent, $this>
     */
    public function timelineEvents(): HasMany
    {
        return $this->hasMany(PatientTimelineEvent::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @return HasMany<ClinicalAssessment, $this>
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(ClinicalAssessment::class);
    }

    /**
     * @return HasMany<ClinicalSoapNote, $this>
     */
    public function soapNotes(): HasMany
    {
        return $this->hasMany(ClinicalSoapNote::class);
    }

    /**
     * @return HasMany<ClinicalTreatmentPlan, $this>
     */
    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(ClinicalTreatmentPlan::class);
    }

    /**
     * @return HasMany<ClinicalTreatmentSession, $this>
     */
    public function treatmentSessions(): HasMany
    {
        return $this->hasMany(ClinicalTreatmentSession::class);
    }

    /**
     * @return HasMany<ClinicalProgressNote, $this>
     */
    public function progressNotes(): HasMany
    {
        return $this->hasMany(ClinicalProgressNote::class);
    }

    /**
     * @return HasMany<ClinicalDischargeSummary, $this>
     */
    public function dischargeSummaries(): HasMany
    {
        return $this->hasMany(ClinicalDischargeSummary::class);
    }

    /**
     * @return HasMany<ClinicalExercisePlan, $this>
     */
    public function exercisePlans(): HasMany
    {
        return $this->hasMany(ClinicalExercisePlan::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<PatientPackage, $this>
     */
    public function patientPackages(): HasMany
    {
        return $this->hasMany(PatientPackage::class);
    }

    /**
     * @return HasMany<Medication, $this>
     */
    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class);
    }

    /**
     * @return HasOne<PatientWellnessPreference, $this>
     */
    public function wellnessPreferences(): HasOne
    {
        return $this->hasOne(PatientWellnessPreference::class);
    }
}
