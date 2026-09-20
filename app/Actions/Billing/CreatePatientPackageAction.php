<?php

namespace App\Actions\Billing;

use App\Models\BillingPackage;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\PatientPackage;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreatePatientPackageAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
        private CreateInvoiceAction $createInvoiceAction,
        private AddInvoiceItemAction $addInvoiceItemAction,
    ) {}

    /**
     * Create a pending patient package optionally backed by a draft invoice line.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Patient $patient, array $data): PatientPackage
    {
        return DB::transaction(function () use ($patient, $data): PatientPackage {
            $package = BillingPackage::query()->findOrFail($data['package_id']);

            if (! $package->is_active) {
                throw ValidationException::withMessages([
                    'package_id' => ['This billing package is inactive.'],
                ]);
            }

            $createInvoice = (bool) ($data['create_invoice'] ?? true);
            $invoice = null;

            if ($createInvoice) {
                $invoice = $this->createInvoiceAction->handle([
                    'patient_id' => $patient->id,
                    'clinic_id' => $data['clinic_id'] ?? $package->clinic_id,
                    'currency' => $package->currency,
                    'notes' => $data['notes'] ?? null,
                ]);

                $this->addInvoiceItemAction->handle($invoice, [
                    'type' => InvoiceItem::TYPE_PACKAGE,
                    'description' => $package->name,
                    'quantity' => 1,
                    'unit_price_cents' => $package->price_cents,
                    'discount_cents' => 0,
                    'tax_rate_bps' => $data['tax_rate_bps'] ?? null,
                    'reference_type' => BillingPackage::class,
                    'reference_id' => $package->id,
                ]);
            }

            $patientPackage = PatientPackage::query()->create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => TenantContext::id() ?? $patient->tenant_id,
                'patient_id' => $patient->id,
                'package_id' => $package->id,
                'invoice_id' => $invoice?->id,
                'payment_id' => null,
                'sessions_total' => $package->session_count,
                'sessions_used' => 0,
                'sessions_remaining' => $package->session_count,
                'purchased_at' => now(),
                'expires_at' => $package->validity_days
                    ? now()->addDays($package->validity_days)
                    : null,
                'status' => PatientPackage::STATUS_ACTIVE,
                'payment_status' => PatientPackage::PAYMENT_PENDING,
            ]);

            $this->timelineRecorder->record(
                $patient,
                'billing.package.purchased_pending',
                'Treatment package reserved',
                subject: $patientPackage,
                meta: [
                    'patient_package_uuid' => $patientPackage->uuid,
                    'package_id' => $package->id,
                    'invoice_id' => $invoice?->id,
                ],
            );

            $this->auditLogger->log('billing.patient_package.created', $patientPackage, [
                'patient_uuid' => $patient->uuid,
                'package_id' => $package->id,
                'invoice_id' => $invoice?->id,
            ]);

            return $patientPackage->load(['package', 'invoice']);
        });
    }
}
