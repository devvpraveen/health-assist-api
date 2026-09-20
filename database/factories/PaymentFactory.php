<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'invoice_id' => Invoice::factory(),
            'patient_id' => Patient::factory(),
            'amount_cents' => 10000,
            'currency' => 'INR',
            'method' => Payment::METHOD_CASH,
            'gateway' => 'manual',
            'gateway_reference' => null,
            'status' => Payment::STATUS_COMPLETED,
            'idempotency_key' => null,
            'paid_at' => now(),
            'recorded_by_user_id' => null,
            'meta' => null,
        ];
    }

    public function forInvoice(Invoice $invoice): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $invoice->tenant_id,
            'invoice_id' => $invoice->id,
            'patient_id' => $invoice->patient_id,
            'currency' => $invoice->currency,
        ]);
    }

    public function recordedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'recorded_by_user_id' => $user->id,
        ]);
    }
}
