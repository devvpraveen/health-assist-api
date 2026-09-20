<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Patient;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class InvoiceLifecycleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_create_draft_add_items_issue_sets_number_and_totals(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $create = $this->postJson('/api/v1/invoices', [
            'patient_id' => $patient->id,
            'notes' => 'Physio billing',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.status', Invoice::STATUS_DRAFT)
            ->assertJsonPath('data.number', null)
            ->assertJsonPath('data.currency', 'INR');

        $invoiceId = $create->json('data.id');

        $item = $this->postJson('/api/v1/invoices/'.$invoiceId.'/items', [
            'type' => 'consultation',
            'description' => 'Initial consultation',
            'quantity' => 1,
            'unit_price_cents' => 10000,
            'discount_cents' => 0,
            'tax_rate_bps' => 1800,
        ]);

        $item->assertCreated()
            ->assertJsonPath('data.tax_cents', 1800)
            ->assertJsonPath('data.line_total_cents', 11800);

        $this->getJson('/api/v1/invoices/'.$invoiceId)
            ->assertOk()
            ->assertJsonPath('data.subtotal_cents', 10000)
            ->assertJsonPath('data.tax_cents', 1800)
            ->assertJsonPath('data.total_cents', 11800)
            ->assertJsonPath('data.amount_due_cents', 11800);

        $issue = $this->postJson('/api/v1/invoices/'.$invoiceId.'/issue');

        $issue->assertOk()
            ->assertJsonPath('data.status', Invoice::STATUS_ISSUED)
            ->assertJsonPath('data.amount_due_cents', 11800);

        $this->assertNotNull($issue->json('data.number'));
        $this->assertStringStartsWith('INV-', $issue->json('data.number'));
        $this->assertNotNull($issue->json('data.issued_at'));

        $this->postJson('/api/v1/invoices/'.$invoiceId.'/items', [
            'type' => 'service',
            'description' => 'Should fail',
            'unit_price_cents' => 1000,
        ])->assertForbidden();

        $this->patchJson('/api/v1/invoices/'.$invoiceId, [
            'notes' => 'locked',
        ])->assertForbidden();
    }
}
