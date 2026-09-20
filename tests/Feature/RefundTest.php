<?php

namespace Tests\Feature;

use App\Events\RefundProcessed;
use App\Models\Invoice;
use App\Models\Patient;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_refund_updates_invoice_amounts_and_status(): void
    {
        Event::fake([RefundProcessed::class]);

        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $invoiceId = $this->postJson('/api/v1/invoices', [
            'patient_id' => $patient->id,
        ])->json('data.id');

        $this->postJson('/api/v1/invoices/'.$invoiceId.'/items', [
            'type' => 'treatment',
            'description' => 'Session',
            'unit_price_cents' => 10000,
        ])->assertCreated();

        $this->postJson('/api/v1/invoices/'.$invoiceId.'/issue')->assertOk();

        $paymentId = $this->postJson('/api/v1/payments', [
            'invoice_id' => $invoiceId,
            'amount_cents' => 10000,
            'method' => 'upi',
        ])->json('data.id');

        $refund = $this->postJson('/api/v1/refunds', [
            'payment_id' => $paymentId,
            'amount_cents' => 4000,
            'reason' => 'Partial unused',
        ]);

        $refund->assertCreated()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.amount_cents', 4000);

        $this->getJson('/api/v1/invoices/'.$invoiceId)
            ->assertOk()
            ->assertJsonPath('data.status', Invoice::STATUS_PARTIALLY_PAID)
            ->assertJsonPath('data.amount_paid_cents', 6000)
            ->assertJsonPath('data.amount_due_cents', 4000);

        $this->postJson('/api/v1/refunds', [
            'payment_id' => $paymentId,
            'amount_cents' => 6000,
        ])->assertCreated();

        $this->getJson('/api/v1/invoices/'.$invoiceId)
            ->assertOk()
            ->assertJsonPath('data.status', Invoice::STATUS_REFUNDED)
            ->assertJsonPath('data.amount_paid_cents', 0);

        Event::assertDispatched(RefundProcessed::class);
    }
}
