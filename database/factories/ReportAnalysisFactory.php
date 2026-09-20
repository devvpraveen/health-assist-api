<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\ReportAnalysis;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ReportAnalysis>
 */
class ReportAnalysisFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'patient_id' => fn (array $attributes) => Patient::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'document_id' => fn (array $attributes) => PatientDocument::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
                'patient_id' => $attributes['patient_id'],
            ])->id,
            'health_record_id' => null,
            'status' => ReportAnalysis::STATUS_QUEUED,
            'report_type' => 'lab',
            'ocr_provider' => null,
            'ocr_raw_text' => null,
            'extracted_facts' => null,
            'interpretation' => null,
            'reference_range_findings' => null,
            'safety_level' => null,
            'safety_assessment_id' => null,
            'patient_explanation' => null,
            'clinician_notes' => null,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'ai_usage_record_id' => null,
            'error_message' => null,
        ];
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(fn () => [
            'tenant_id' => $patient->tenant_id,
            'patient_id' => $patient->id,
        ]);
    }

    public function awaitingReview(): static
    {
        return $this->state(fn () => [
            'status' => ReportAnalysis::STATUS_AWAITING_REVIEW,
            'ocr_provider' => 'mock',
            'ocr_raw_text' => "Hb: 11.2 g/dL\nWBC: 12.5 x10^3/uL\nGlucose: 110 mg/dL",
            'extracted_facts' => [
                'tests' => [
                    ['name' => 'hb', 'value' => 11.2, 'unit' => 'g/dL', 'flag' => 'low'],
                ],
                'source' => 'heuristic_ocr',
            ],
            'interpretation' => [
                'possible_interpretation' => 'Mock interpretation',
                'uncertainty' => 'Unknown context',
                'items_requiring_review' => ['Clinician review required'],
                'requires_clinician_review' => true,
            ],
            'reference_range_findings' => [
                ['name' => 'hb', 'value' => 11.2, 'unit' => 'g/dL', 'flag' => 'low', 'low' => 12.0, 'high' => 17.5],
            ],
            'patient_explanation' => 'Assistive patient explanation draft.',
            'safety_level' => 'routine',
        ]);
    }
}
