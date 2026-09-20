<?php

namespace App\Actions\Billing;

use App\Contracts\Billing\PaymentGatewayInterface;
use App\Events\PaymentReceived;
use App\Models\BillingPackage;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PatientPackage;
use App\Models\Payment;
use App\Models\Receipt;
use App\Services\AuditLogger;
use App\Services\Billing\BillingNumberGenerator;
use App\Services\Billing\PaymentIntent;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RecordPaymentAction
{
    public function __construct(
        private PaymentGatewayInterface $gateway,
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
        private BillingNumberGenerator $numberGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Payment
    {
        return DB::transaction(function () use ($data): Payment {
            /** @var Invoice $invoice */
            $invoice = Invoice::query()->whereKey($data['invoice_id'])->lockForUpdate()->firstOrFail();

            if (in_array($invoice->status, [
                Invoice::STATUS_DRAFT,
                Invoice::STATUS_CANCELLED,
                Invoice::STATUS_REFUNDED,
            ], true)) {
                throw ValidationException::withMessages([
                    'invoice_id' => ['Payments can only be recorded against issued invoices.'],
                ]);
            }

            $idempotencyKey = $data['idempotency_key'] ?? null;

            if (is_string($idempotencyKey) && $idempotencyKey !== '') {
                $existing = Payment::query()
                    ->where('tenant_id', $invoice->tenant_id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing !== null) {
                    return $existing->load(['receipt', 'invoice']);
                }
            } else {
                $idempotencyKey = null;
            }

            $amountCents = (int) $data['amount_cents'];

            if ($amountCents < 1) {
                throw ValidationException::withMessages([
                    'amount_cents' => ['Payment amount must be at least 1 cent.'],
                ]);
            }

            if ($amountCents > $invoice->amount_due_cents) {
                throw ValidationException::withMessages([
                    'amount_cents' => ['Payment cannot exceed amount due ('.$invoice->amount_due_cents.' cents).'],
                ]);
            }

            $method = $data['method'];
            $currency = $data['currency'] ?? $invoice->currency;
            $meta = $data['meta'] ?? [];

            if ($idempotencyKey !== null) {
                $meta['idempotency_key'] = $idempotencyKey;
            }

            $result = $this->gateway->charge(new PaymentIntent(
                amountCents: $amountCents,
                currency: $currency,
                method: $method,
                gatewayReference: $data['gateway_reference'] ?? null,
                meta: is_array($meta) ? $meta : [],
            ));

            if (! $result->success) {
                throw ValidationException::withMessages([
                    'payment' => [$result->message ?? 'Payment gateway declined the charge.'],
                ]);
            }

            $payment = Payment::query()->create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => TenantContext::id() ?? $invoice->tenant_id,
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'amount_cents' => $amountCents,
                'currency' => $currency,
                'method' => $method,
                'gateway' => config('billing.default_gateway', 'manual'),
                'gateway_reference' => $result->gatewayReference,
                'status' => Payment::STATUS_COMPLETED,
                'idempotency_key' => $idempotencyKey,
                'paid_at' => now(),
                'recorded_by_user_id' => Auth::id(),
                'meta' => $meta ?: null,
            ]);

            $newPaid = $invoice->amount_paid_cents + $amountCents;
            $newDue = max(0, $invoice->total_cents - $newPaid);

            $status = Invoice::STATUS_PARTIALLY_PAID;
            $paidAt = null;

            if ($newDue === 0) {
                $status = Invoice::STATUS_PAID;
                $paidAt = now();
            } elseif ($invoice->status === Invoice::STATUS_OVERDUE && $newPaid > 0) {
                $status = Invoice::STATUS_PARTIALLY_PAID;
            } elseif ($invoice->status === Invoice::STATUS_ISSUED) {
                $status = $newPaid > 0 && $newDue > 0
                    ? Invoice::STATUS_PARTIALLY_PAID
                    : Invoice::STATUS_ISSUED;
            }

            $invoice->forceFill([
                'amount_paid_cents' => $newPaid,
                'amount_due_cents' => $newDue,
                'status' => $status,
                'paid_at' => $paidAt ?? $invoice->paid_at,
            ])->save();

            $receipt = Receipt::query()->create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $payment->tenant_id,
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'number' => $this->numberGenerator->nextReceiptNumber($payment->tenant_id),
                'amount_cents' => $amountCents,
                'currency' => $currency,
                'issued_at' => now(),
            ]);

            $this->activatePackagePurchases($invoice, $payment);

            $this->timelineRecorder->record(
                $invoice->patient,
                'billing.payment.received',
                'Payment received',
                subject: $payment,
                meta: [
                    'payment_uuid' => $payment->uuid,
                    'invoice_uuid' => $invoice->uuid,
                    'receipt_number' => $receipt->number,
                    'amount_cents' => $amountCents,
                    'method' => $method,
                ],
            );

            $this->auditLogger->log('billing.payment.recorded', $payment, [
                'payment_uuid' => $payment->uuid,
                'invoice_id' => $invoice->id,
                'amount_cents' => $amountCents,
                'receipt_id' => $receipt->id,
            ]);

            event(new PaymentReceived($payment));

            return $payment->load(['receipt', 'invoice']);
        });
    }

    protected function activatePackagePurchases(Invoice $invoice, Payment $payment): void
    {
        $packageItems = $invoice->items()
            ->where('type', InvoiceItem::TYPE_PACKAGE)
            ->get();

        foreach ($packageItems as $item) {
            $packageId = $item->reference_id;

            if ($packageId === null) {
                continue;
            }

            $catalog = BillingPackage::query()->find($packageId);

            if ($catalog === null) {
                continue;
            }

            $existing = PatientPackage::query()
                ->where('invoice_id', $invoice->id)
                ->where('package_id', $catalog->id)
                ->first();

            if ($existing !== null) {
                $existing->forceFill([
                    'payment_id' => $payment->id,
                    'payment_status' => PatientPackage::PAYMENT_PAID,
                    'purchased_at' => $existing->purchased_at ?? now(),
                    'expires_at' => $existing->expires_at ?? (
                        $catalog->validity_days ? now()->addDays($catalog->validity_days) : null
                    ),
                    'status' => PatientPackage::STATUS_ACTIVE,
                ])->save();

                continue;
            }

            PatientPackage::query()->create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $invoice->tenant_id,
                'patient_id' => $invoice->patient_id,
                'package_id' => $catalog->id,
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'sessions_total' => $catalog->session_count * max(1, $item->quantity),
                'sessions_used' => 0,
                'sessions_remaining' => $catalog->session_count * max(1, $item->quantity),
                'purchased_at' => now(),
                'expires_at' => $catalog->validity_days
                    ? now()->addDays($catalog->validity_days)
                    : null,
                'status' => PatientPackage::STATUS_ACTIVE,
                'payment_status' => PatientPackage::PAYMENT_PAID,
            ]);
        }
    }
}
