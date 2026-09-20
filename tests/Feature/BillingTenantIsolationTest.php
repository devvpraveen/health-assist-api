<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class BillingTenantIsolationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_cross_tenant_billing_resources_are_hidden(): void
    {
        [$userA] = $this->createTenantUserWithOrg('Tenant A');
        [, , $tenantB] = $this->createTenantUserWithOrg('Tenant B');

        $patientB = Patient::factory()->forTenant($tenantB)->create();
        $invoiceB = Invoice::factory()->forPatient($patientB)->issued()->create([
            'total_cents' => 5000,
            'amount_due_cents' => 5000,
            'issued_by_user_id' => null,
        ]);
        $paymentB = Payment::factory()->forInvoice($invoiceB)->create();

        Sanctum::actingAs($userA);
        TenantContext::set($userA->tenant_id);

        $this->getJson('/api/v1/invoices/'.$invoiceB->id)
            ->assertNotFound();

        $this->getJson('/api/v1/payments/'.$paymentB->id)
            ->assertNotFound();

        $this->getJson('/api/v1/patients/'.$patientB->id.'/packages')
            ->assertNotFound();
    }
}
