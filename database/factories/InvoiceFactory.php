<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'patient_id' => Patient::factory(),
            'clinic_id' => null,
            'appointment_id' => null,
            'issued_by_user_id' => null,
            'number' => null,
            'status' => Invoice::STATUS_DRAFT,
            'currency' => 'INR',
            'subtotal_cents' => 0,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'amount_paid_cents' => 0,
            'amount_due_cents' => 0,
            'issued_at' => null,
            'due_at' => null,
            'paid_at' => null,
            'cancelled_at' => null,
            'notes' => null,
            'meta' => null,
        ];
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $patient->tenant_id,
            'patient_id' => $patient->id,
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function issued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_ISSUED,
            'number' => 'INV-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'issued_at' => now(),
            'issued_by_user_id' => User::factory(),
            'total_cents' => 10000,
            'subtotal_cents' => 10000,
            'amount_due_cents' => 10000,
        ]);
    }
}
