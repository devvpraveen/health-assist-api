<?php

namespace Tests\Feature;

use App\Models\HealthRecord;
use App\Models\Patient;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class HealthRecordTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_health_record_crud_sets_integrity_hash(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $create = $this->postJson('/api/v1/patients/'.$patient->id.'/health-records', [
            'category' => 'laboratory',
            'title' => 'CBC Panel',
            'description' => 'Complete blood count',
            'recorded_at' => '2026-01-15T10:00:00Z',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.category', 'laboratory')
            ->assertJsonPath('data.title', 'CBC Panel')
            ->assertJsonPath('data.integrity_status', 'disabled');

        $this->assertNotEmpty($create->json('data.integrity_hash'));
        $this->assertSame(64, strlen($create->json('data.integrity_hash')));

        $recordId = $create->json('data.id');

        $this->getJson('/api/v1/patients/'.$patient->id.'/health-records')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->putJson('/api/v1/patients/'.$patient->id.'/health-records/'.$recordId, [
            'title' => 'CBC Panel Updated',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'CBC Panel Updated');

        $this->assertNotEmpty(
            HealthRecord::query()->findOrFail($recordId)->integrity_hash
        );

        $this->deleteJson('/api/v1/patients/'.$patient->id.'/health-records/'.$recordId)
            ->assertNoContent();
    }

    public function test_health_record_rejects_invalid_category(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/patients/'.$patient->id.'/health-records', [
            'category' => 'not-a-real-category',
            'title' => 'Bad category',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['category']);
    }
}
