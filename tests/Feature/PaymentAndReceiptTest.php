<?php

namespace Tests\Feature;

use App\Events\PaymentReceived;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Receipt;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class PaymentAndReceiptTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_full_payment_marks_paid_and_creates_receipt(): void
    {
        Event::fake([PaymentReceived::class]);

        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $invoiceId = $this->createIssuedInvoice($patient->id, 10000);

        $pay = $this->postJson('/api/v1/payments', [
            'invoice_id' => $invoiceId,
            'amount_cents' => 10000,
            'method' => 'cash',
        ]);

        $pay->assertCreated()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.amount_cents', 10000)
            ->assertJsonPath('data.gateway', 'manual');

        $this->assertNotNull($pay->json('data.receipt.number'));
        $this->assertStringStartsWith('RCP-', $pay->json('data.receipt.number'));

        $this->getJson('/api/v1/invoices/'.$invoiceId)
            ->assertOk()
            ->assertJsonPath('data.status', Invoice::STATUS_PAID)
            ->assertJsonPath('data.amount_paid_cents', 10000)
            ->assertJsonPath('data.amount_due_cents', 0);

        $this->assertDatabaseCount('receipts', 1);
        Event::assertDispatched(PaymentReceived::class);
    }

    public function test_partial_payment_marks_partially_paid(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $invoiceId = $this->createIssuedInvoice($patient->id, 10000);

        $this->postJson('/api/v1/payments', [
            'invoice_id' => $invoiceId,
            'amount_cents' => 4000,
            'method' => 'upi',
        ])->assertCreated();

        $this->getJson('/api/v1/invoices/'.$invoiceId)
            ->assertOk()
            ->assertJsonPath('data.status', Invoice::STATUS_PARTIALLY_PAID)
            ->assertJsonPath('data.amount_paid_cents', 4000)
            ->assertJsonPath('data.amount_due_cents', 6000);

        $this->postJson('/api/v1/payments', [
            'invoice_id' => $invoiceId,
            'amount_cents' => 7000,
            'method' => 'cash',
        ])->assertUnprocessable();
    }

    public function test_idempotency_key_returns_existing_payment(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $invoiceId = $this->createIssuedInvoice($patient->id, 5000);

        $first = $this->postJson('/api/v1/payments', [
            'invoice_id' => $invoiceId,
            'amount_cents' => 5000,
            'method' => 'card',
            'idempotency_key' => 'pay-key-1',
        ])->assertCreated();

        $second = $this->postJson('/api/v1/payments', [
            'invoice_id' => $invoiceId,
            'amount_cents' => 5000,
            'method' => 'card',
            'idempotency_key' => 'pay-key-1',
        ])->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(1, Receipt::query()->count());
    }

    private function createIssuedInvoice(int $patientId, int $amountCents): int
    {
        $invoiceId = $this->postJson('/api/v1/invoices', [
            'patient_id' => $patientId,
        ])->json('data.id');

        $this->postJson('/api/v1/invoices/'.$invoiceId.'/items', [
            'type' => 'service',
            'description' => 'Service',
            'unit_price_cents' => $amountCents,
        ])->assertCreated();

        $this->postJson('/api/v1/invoices/'.$invoiceId.'/issue')->assertOk();

        return $invoiceId;
    }
}
