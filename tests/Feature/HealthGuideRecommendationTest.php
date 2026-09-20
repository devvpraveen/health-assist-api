<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Schedule;
use App\Models\Specialty;
use App\Support\TenantContext;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SafetyRulesSeeder;
use Database\Seeders\SpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class HealthGuideRecommendationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SpecialtySeeder::class);
        $this->seed(AiCoreSeeder::class);
        $this->seed(SafetyRulesSeeder::class);
    }

    public function test_knee_pain_message_ranks_physio_providers(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create(['city' => 'Bengaluru']);
        $patient = Patient::factory()->forTenant($user->tenant)->create();
        $physio = Specialty::query()->where('slug', 'physiotherapy')->firstOrFail();
        $dental = Specialty::query()->where('slug', 'dentistry')->firstOrFail();

        $physioProvider = Provider::factory()->forClinic($clinic)->create([
            'display_name' => 'Dr. Priya Sharma',
            'type' => 'physiotherapist',
            'years_experience' => 8,
            'verification_status' => 'verified',
            'is_public' => true,
            'status' => 'active',
            'bio' => 'Knee and sports physiotherapy',
        ]);
        $physioProvider->specialties()->sync([$physio->id => ['is_primary' => true]]);
        Schedule::factory()->forProvider($physioProvider)->create(['is_active' => true]);

        $dentist = Provider::factory()->forClinic($clinic)->create([
            'display_name' => 'Dr. Dental Only',
            'type' => 'doctor',
            'years_experience' => 12,
            'verification_status' => 'verified',
            'is_public' => true,
            'status' => 'active',
        ]);
        $dentist->specialties()->sync([$dental->id => ['is_primary' => true]]);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $uuid = $this->postJson('/api/v1/health-guide/conversations', [
            'patient_id' => $patient->id,
        ])->assertCreated()->json('data.uuid');

        $response = $this->postJson("/api/v1/health-guide/conversations/{$uuid}/messages", [
            'content' => "I've had mild knee pain for two weeks and want to find a provider",
        ])->assertOk();

        $this->assertSame('knee_pain', $response->json('structured_state.complaint'));
        $this->assertNotEmpty($response->json('recommendations'));
        $this->assertSame($physioProvider->id, $response->json('recommendations.0.id'));
        $this->assertTrue($response->json('recommendations.0.specialty_match'));
    }
}
