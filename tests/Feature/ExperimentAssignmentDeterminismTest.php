<?php

namespace Tests\Feature;

use App\Models\Experiment;
use App\Models\ExperimentVariant;
use App\Services\Marketing\ExperimentAssignmentService;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class ExperimentAssignmentDeterminismTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_assignment_is_deterministic_for_same_anonymous_id(): void
    {
        [$user] = $this->createTenantUserWithOrg();

        $experiment = Experiment::factory()->running()->create([
            'tenant_id' => $user->tenant_id,
            'tenant_key' => 'tenant:'.$user->tenant_id,
            'key' => 'homepage_cta',
        ]);

        ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->id,
            'key' => 'control',
            'weight' => 50,
        ]);
        ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->id,
            'key' => 'variant_b',
            'weight' => 50,
        ]);

        $service = app(ExperimentAssignmentService::class);
        $a = $service->assign($experiment, 'anon-stable-1');
        $b = $service->assign($experiment, 'anon-stable-1');

        $this->assertSame($a->variant_key, $b->variant_key);
        $this->assertSame($a->id, $b->id);

        $again = $service->pickVariantKey(
            'anon-stable-1',
            'homepage_cta',
            $experiment->variants()->orderBy('id')->get(),
        );
        $this->assertSame($a->variant_key, $again);

        $this->postJson('/api/v1/public/experiments/homepage_cta/assign', [
            'anonymous_id' => 'anon-stable-1',
        ])->assertOk()
            ->assertJsonPath('data.variant_key', $a->variant_key);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/experiments/homepage_cta/expose', [
            'anonymous_id' => 'anon-stable-1',
            'variant' => $a->variant_key,
        ])->assertStatus(202);
    }
}
