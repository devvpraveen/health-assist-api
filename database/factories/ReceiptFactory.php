<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
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
            'number' => 'RCP-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'amount_cents' => 10000,
            'currency' => 'INR',
            'issued_at' => now(),
        ];
    }

    public function forPayment(Payment $payment): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $payment->tenant_id,
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'patient_id' => $payment->patient_id,
            'amount_cents' => $payment->amount_cents,
            'currency' => $payment->currency,
        ]);
    }
}
