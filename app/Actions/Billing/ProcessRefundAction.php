<?php

namespace App\Actions\Billing;

use App\Contracts\Billing\PaymentGatewayInterface;
use App\Events\RefundProcessed;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\AuditLogger;
use App\Services\Billing\RefundIntent;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProcessRefundAction
{
    public function __construct(
        private PaymentGatewayInterface $gateway,
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Refund
    {
        return DB::transaction(function () use ($data): Refund {
            /** @var Payment $payment */
            $payment = Payment::query()->whereKey($data['payment_id'])->lockForUpdate()->firstOrFail();

            if ($payment->status !== Payment::STATUS_COMPLETED) {
                throw ValidationException::withMessages([
                    'payment_id' => ['Only completed payments can be refunded.'],
                ]);
            }

            /** @var Invoice $invoice */
            $invoice = Invoice::query()->whereKey($payment->invoice_id)->lockForUpdate()->firstOrFail();

            $amountCents = (int) $data['amount_cents'];

            if ($amountCents < 1) {
                throw ValidationException::withMessages([
                    'amount_cents' => ['Refund amount must be at least 1 cent.'],
                ]);
            }

            $alreadyRefunded = (int) Refund::query()
                ->where('payment_id', $payment->id)
                ->where('status', Refund::STATUS_COMPLETED)
                ->sum('amount_cents');

            $refundable = $payment->amount_cents - $alreadyRefunded;

            if ($amountCents > $refundable) {
                throw ValidationException::withMessages([
                    'amount_cents' => ['Refund cannot exceed remaining refundable amount ('.$refundable.' cents).'],
                ]);
            }

            $result = $this->gateway->refund(new RefundIntent(
                amountCents: $amountCents,
                currency: $payment->currency,
                gatewayReference: $data['gateway_reference'] ?? null,
                reason: $data['reason'] ?? null,
            ));

            if (! $result->success) {
                throw ValidationException::withMessages([
                    'refund' => [$result->message ?? 'Payment gateway declined the refund.'],
                ]);
            }

            $refund = Refund::query()->create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => TenantContext::id() ?? $payment->tenant_id,
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'patient_id' => $payment->patient_id,
                'amount_cents' => $amountCents,
                'currency' => $payment->currency,
                'reason' => $data['reason'] ?? null,
                'status' => Refund::STATUS_COMPLETED,
                'gateway' => config('billing.default_gateway', 'manual'),
                'gateway_reference' => $result->gatewayReference,
                'processed_at' => now(),
                'recorded_by_user_id' => Auth::id(),
            ]);

            $newPaid = max(0, $invoice->amount_paid_cents - $amountCents);
            $newDue = max(0, $invoice->total_cents - $newPaid);

            $status = match (true) {
                $newPaid === 0 => Invoice::STATUS_REFUNDED,
                $newPaid < $invoice->total_cents => Invoice::STATUS_PARTIALLY_PAID,
                default => Invoice::STATUS_PAID,
            };

            $invoice->forceFill([
                'amount_paid_cents' => $newPaid,
                'amount_due_cents' => $newDue,
                'status' => $status,
                'paid_at' => $status === Invoice::STATUS_PAID ? ($invoice->paid_at ?? now()) : null,
            ])->save();

            $this->timelineRecorder->record(
                $invoice->patient,
                'billing.refund.processed',
                'Refund processed',
                subject: $refund,
                meta: [
                    'refund_uuid' => $refund->uuid,
                    'payment_uuid' => $payment->uuid,
                    'invoice_uuid' => $invoice->uuid,
                    'amount_cents' => $amountCents,
                ],
            );

            $this->auditLogger->log('billing.refund.recorded', $refund, [
                'refund_uuid' => $refund->uuid,
                'payment_id' => $payment->id,
                'amount_cents' => $amountCents,
            ]);

            event(new RefundProcessed($refund));

            return $refund->load(['payment', 'invoice']);
        });
    }
}
