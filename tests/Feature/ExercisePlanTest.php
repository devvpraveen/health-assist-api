<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\Patient;
use App\Support\TenantContext;
use Database\Seeders\ExerciseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class ExercisePlanTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ExerciseSeeder::class);
    }

    public function test_library_plan_items_sync_and_adherence_log(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();
        $libraryExercise = Exercise::query()->where('slug', 'ankle_pumps')->firstOrFail();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->getJson('/api/v1/exercises')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'ankle_pumps']);

        $tenantExercise = $this->postJson('/api/v1/exercises', [
            'name' => 'Wall Slides',
            'slug' => 'wall_slides',
            'category' => 'lower_limb',
            'default_sets' => 3,
            'default_reps' => 12,
        ]);

        $tenantExercise->assertCreated()
            ->assertJsonPath('data.slug', 'wall_slides')
            ->assertJsonPath('data.tenant_id', $tenant->id);

        $plan = $this->postJson('/api/v1/patients/'.$patient->id.'/exercise-plans', [
            'title' => 'Home ankle program',
            'start_date' => '2026-09-05',
            'notes' => 'Twice daily',
        ]);

        $plan->assertCreated()
            ->assertJsonPath('data.title', 'Home ankle program')
            ->assertJsonPath('data.status', 'active');

        $planId = $plan->json('data.id');

        $sync = $this->putJson('/api/v1/patients/'.$patient->id.'/exercise-plans/'.$planId.'/items', [
            'items' => [
                [
                    'exercise_id' => $libraryExercise->id,
                    'sets' => 3,
                    'reps' => 20,
                    'frequency' => '2x/day',
                    'sort_order' => 0,
                ],
                [
                    'custom_name' => 'Ice pack',
                    'duration_seconds' => 900,
                    'frequency' => 'after activity',
                    'sort_order' => 1,
                ],
            ],
        ]);

        $sync->assertOk()
            ->assertJsonCount(2, 'data.items');

        $itemId = $sync->json('data.items.0.id');

        $log = $this->postJson(
            '/api/v1/patients/'.$patient->id.'/exercise-plans/'.$planId.'/items/'.$itemId.'/logs',
            [
                'performed_at' => '2026-09-06T08:00:00Z',
                'result' => 'completed',
                'pain_score' => 2,
                'notes' => 'Felt good',
            ]
        );

        $log->assertCreated()
            ->assertJsonPath('data.result', 'completed')
            ->assertJsonPath('data.pain_score', 2);

        $this->getJson('/api/v1/patients/'.$patient->id.'/exercise-plans/'.$planId.'/logs')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
