<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'payment_id' => Payment::factory(),
            'invoice_id' => Invoice::factory(),
            'patient_id' => Patient::factory(),
            'amount_cents' => 5000,
            'currency' => 'INR',
            'reason' => 'Customer request',
            'status' => Refund::STATUS_COMPLETED,
            'gateway' => 'manual',
            'gateway_reference' => null,
            'processed_at' => now(),
            'recorded_by_user_id' => null,
        ];
    }

    public function forPayment(Payment $payment): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $payment->tenant_id,
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'patient_id' => $payment->patient_id,
            'currency' => $payment->currency,
        ]);
    }
}
