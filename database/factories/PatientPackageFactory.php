<?php

namespace Database\Factories;

use App\Models\BillingPackage;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientPackage;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PatientPackage>
 */
class PatientPackageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sessions = 10;

        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'patient_id' => Patient::factory(),
            'package_id' => BillingPackage::factory(),
            'invoice_id' => null,
            'payment_id' => null,
            'sessions_total' => $sessions,
            'sessions_used' => 0,
            'sessions_remaining' => $sessions,
            'purchased_at' => now(),
            'expires_at' => now()->addDays(90),
            'status' => PatientPackage::STATUS_ACTIVE,
            'payment_status' => PatientPackage::PAYMENT_PAID,
        ];
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $patient->tenant_id,
            'patient_id' => $patient->id,
        ]);
    }

    public function forPackage(BillingPackage $package): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $package->tenant_id,
            'package_id' => $package->id,
            'sessions_total' => $package->session_count,
            'sessions_remaining' => $package->session_count,
        ]);
    }

    public function withInvoice(Invoice $invoice): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => $invoice->id,
        ]);
    }

    public function withPayment(Payment $payment): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_id' => $payment->id,
            'payment_status' => PatientPackage::PAYMENT_PAID,
        ]);
    }
}
