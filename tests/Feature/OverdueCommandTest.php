<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Patient;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class OverdueCommandTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_command_marks_past_due_invoices_overdue(): void
    {
        [, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        TenantContext::set($tenant->id);

        $overdue = Invoice::factory()->forPatient($patient)->issued()->create([
            'status' => Invoice::STATUS_ISSUED,
            'due_at' => CarbonImmutable::now()->subDay(),
            'total_cents' => 10000,
            'amount_due_cents' => 10000,
            'issued_by_user_id' => null,
        ]);

        $partial = Invoice::factory()->forPatient($patient)->issued()->create([
            'status' => Invoice::STATUS_PARTIALLY_PAID,
            'due_at' => CarbonImmutable::now()->subHours(2),
            'total_cents' => 10000,
            'amount_paid_cents' => 2000,
            'amount_due_cents' => 8000,
            'issued_by_user_id' => null,
        ]);

        $future = Invoice::factory()->forPatient($patient)->issued()->create([
            'status' => Invoice::STATUS_ISSUED,
            'due_at' => CarbonImmutable::now()->addDay(),
            'total_cents' => 10000,
            'amount_due_cents' => 10000,
            'issued_by_user_id' => null,
        ]);

        TenantContext::clear();

        $this->artisan('billing:mark-overdue')->assertSuccessful();

        $this->assertSame(Invoice::STATUS_OVERDUE, $overdue->fresh()->status);
        $this->assertSame(Invoice::STATUS_OVERDUE, $partial->fresh()->status);
        $this->assertSame(Invoice::STATUS_ISSUED, $future->fresh()->status);
    }
}
