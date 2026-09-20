<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\WellnessCategory;
use App\Models\WellnessContent;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WellnessCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class WellnessPersonalizationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(WellnessCategorySeeder::class);
    }

    public function test_preferences_drive_recommendation_order(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($user->tenant)->create();

        $hydration = WellnessCategory::query()->where('slug', 'hydration')->firstOrFail();
        $sleep = WellnessCategory::query()->where('slug', 'sleep')->firstOrFail();

        $hydrationContent = WellnessContent::factory()->platform()->published()->create([
            'category_id' => $hydration->id,
            'title' => 'Hydration first',
            'personalization_tags' => ['hydration', 'desk_worker'],
            'published_at' => now()->subDay(),
        ]);

        $sleepContent = WellnessContent::factory()->platform()->published()->create([
            'category_id' => $sleep->id,
            'title' => 'Sleep second',
            'personalization_tags' => ['sleep'],
            'published_at' => now()->subDays(10),
        ]);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->putJson("/api/v1/patients/{$patient->id}/wellness-preferences", [
            'interests' => ['hydration'],
            'goals' => ['desk_worker'],
            'excluded_tags' => [],
            'reminder_opt_in' => true,
        ])->assertOk()
            ->assertJsonPath('data.interests.0', 'hydration');

        $response = $this->getJson("/api/v1/patients/{$patient->id}/wellness-recommendations?limit=5")
            ->assertOk()
            ->assertJsonPath('disclaimer', config('wellness.disclaimer'));

        $titles = collect($response->json('data'))->pluck('content.title')->all();

        $this->assertSame('Hydration first', $titles[0]);
        $this->assertContains('Sleep second', $titles);
        $this->assertTrue(
            array_search('Hydration first', $titles, true) < array_search('Sleep second', $titles, true)
        );

        $this->assertDatabaseHas('wellness_recommendation_logs', [
            'patient_id' => $patient->id,
            'content_id' => $hydrationContent->id,
        ]);

        $this->assertDatabaseHas('wellness_recommendation_logs', [
            'patient_id' => $patient->id,
            'content_id' => $sleepContent->id,
        ]);
    }
}
