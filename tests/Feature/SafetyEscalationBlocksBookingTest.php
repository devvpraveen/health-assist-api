<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\HealthGuideConversation;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Schedule;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SafetyRulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class SafetyEscalationBlocksBookingTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);
        $this->seed(SafetyRulesSeeder::class);
    }

    public function test_emergency_conversation_cannot_book(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $provider = Provider::factory()->forClinic($clinic)->create(['is_public' => true, 'status' => 'active']);
        $patient = Patient::factory()->forTenant($user->tenant)->create();

        $startsAt = CarbonImmutable::now()->addDays(3)->setTime(10, 0);
        Schedule::factory()->forProvider($provider)->create([
            'day_of_week' => $startsAt->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $uuid = $this->postJson('/api/v1/health-guide/conversations', [
            'patient_id' => $patient->id,
        ])->assertCreated()->json('data.uuid');

        $this->postJson("/api/v1/health-guide/conversations/{$uuid}/messages", [
            'content' => 'I have crushing chest pain and difficulty breathing',
        ])->assertOk()
            ->assertJsonPath('safety.level', 'emergency')
            ->assertJsonPath('conversation.status', HealthGuideConversation::STATUS_ESCALATED)
            ->assertJsonCount(0, 'recommendations');

        $this->postJson("/api/v1/health-guide/conversations/{$uuid}/book", [
            'provider_id' => $provider->id,
            'clinic_id' => $clinic->id,
            'starts_at' => $startsAt->toIso8601String(),
            'confirm' => true,
        ])->assertStatus(422);
    }
}
