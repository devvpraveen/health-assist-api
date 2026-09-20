<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PatientDocument>
 */
class PatientDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return [
            'uuid' => $uuid,
            'tenant_id' => Tenant::factory(),
            'patient_id' => fn (array $attributes) => Patient::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'health_record_id' => null,
            'category' => fake()->optional()->randomElement(['lab', 'imaging', 'other']),
            'original_filename' => 'report.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'disk' => 'local',
            'path' => 'patient-documents/placeholder/'.$uuid.'.pdf',
            'checksum' => hash('sha256', 'placeholder'),
            'visibility' => 'private',
            'uploaded_by' => null,
            'integrity_hash' => null,
            'integrity_status' => 'disabled',
            'integrity_provider' => null,
            'integrity_proof_ref' => null,
            'integrity_attested_at' => null,
        ];
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $patient->tenant_id,
            'patient_id' => $patient->id,
            'path' => sprintf(
                'patient-documents/%d/%s/%s.pdf',
                $patient->tenant_id,
                $patient->uuid,
                $attributes['uuid'] ?? (string) Str::uuid(),
            ),
        ]);
    }
}
