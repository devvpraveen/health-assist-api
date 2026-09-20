<?php

namespace Tests\Feature;

use App\Models\BillingPackage;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\PatientPackage;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class PackagePurchaseAndConsumeTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_paying_package_invoice_creates_patient_package_and_consume_works(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $package = BillingPackage::factory()->forTenant($tenant)->create([
            'session_count' => 2,
            'validity_days' => 30,
            'price_cents' => 20000,
        ]);

        $invoiceId = $this->postJson('/api/v1/invoices', [
            'patient_id' => $patient->id,
        ])->json('data.id');

        $this->postJson('/api/v1/invoices/'.$invoiceId.'/items', [
            'type' => InvoiceItem::TYPE_PACKAGE,
            'description' => $package->name,
            'unit_price_cents' => $package->price_cents,
            'reference_id' => $package->id,
        ])->assertCreated();

        $this->postJson('/api/v1/invoices/'.$invoiceId.'/issue')->assertOk();

        $this->postJson('/api/v1/payments', [
            'invoice_id' => $invoiceId,
            'amount_cents' => 20000,
            'method' => 'cash',
        ])->assertCreated();

        $this->assertDatabaseHas('patient_packages', [
            'patient_id' => $patient->id,
            'package_id' => $package->id,
            'invoice_id' => $invoiceId,
            'sessions_total' => 2,
            'sessions_remaining' => 2,
            'payment_status' => PatientPackage::PAYMENT_PAID,
            'status' => PatientPackage::STATUS_ACTIVE,
        ]);

        $patientPackage = PatientPackage::query()->where('patient_id', $patient->id)->firstOrFail();

        $this->postJson('/api/v1/patients/'.$patient->id.'/packages/'.$patientPackage->id.'/consume')
            ->assertOk()
            ->assertJsonPath('data.sessions_used', 1)
            ->assertJsonPath('data.sessions_remaining', 1);

        $this->postJson('/api/v1/patients/'.$patient->id.'/packages/'.$patientPackage->id.'/consume')
            ->assertOk()
            ->assertJsonPath('data.sessions_remaining', 0)
            ->assertJsonPath('data.status', PatientPackage::STATUS_EXHAUSTED);

        $this->postJson('/api/v1/patients/'.$patient->id.'/packages/'.$patientPackage->id.'/consume')
            ->assertUnprocessable();
    }

    public function test_patient_package_endpoint_creates_pending_with_invoice(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $packageId = $this->postJson('/api/v1/billing-packages', [
            'name' => '10 Session Pack',
            'slug' => 'ten-session-pack',
            'session_count' => 10,
            'validity_days' => 90,
            'price_cents' => 500000,
        ])->json('data.id');

        $created = $this->postJson('/api/v1/patients/'.$patient->id.'/packages', [
            'package_id' => $packageId,
        ]);

        $created->assertCreated()
            ->assertJsonPath('data.payment_status', PatientPackage::PAYMENT_PENDING)
            ->assertJsonPath('data.sessions_total', 10);

        $this->assertNotNull($created->json('data.invoice_id'));
    }
}
