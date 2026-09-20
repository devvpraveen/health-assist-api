<?php

namespace App\Actions\Billing;

use App\Models\Invoice;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateInvoiceAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Invoice
    {
        return DB::transaction(function () use ($data): Invoice {
            $patient = Patient::query()->findOrFail($data['patient_id']);

            $invoice = Invoice::query()->create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => TenantContext::id() ?? $patient->tenant_id,
                'patient_id' => $patient->id,
                'clinic_id' => $data['clinic_id'] ?? null,
                'appointment_id' => $data['appointment_id'] ?? null,
                'issued_by_user_id' => Auth::id(),
                'status' => Invoice::STATUS_DRAFT,
                'currency' => $data['currency'] ?? config('billing.currency', 'INR'),
                'notes' => $data['notes'] ?? null,
                'meta' => $data['meta'] ?? null,
                'due_at' => $data['due_at'] ?? null,
            ]);

            $this->timelineRecorder->record(
                $patient,
                'billing.invoice.created',
                'Invoice draft created',
                subject: $invoice,
                meta: [
                    'invoice_uuid' => $invoice->uuid,
                ],
            );

            $this->auditLogger->log('billing.invoice.created', $invoice, [
                'invoice_uuid' => $invoice->uuid,
                'patient_id' => $patient->id,
            ]);

            return $invoice->load('items');
        });
    }
}
