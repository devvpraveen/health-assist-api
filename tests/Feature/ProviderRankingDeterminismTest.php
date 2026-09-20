<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Provider;
use App\Models\Schedule;
use App\Models\Specialty;
use App\Services\Providers\ProviderRankingService;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class ProviderRankingDeterminismTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SpecialtySeeder::class);
    }

    public function test_same_input_produces_same_order_and_scores(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        TenantContext::set($user->tenant_id);

        $clinic = Clinic::factory()->forOrganization($organization)->create(['city' => 'Bengaluru']);
        $physio = Specialty::query()->where('slug', 'physiotherapy')->firstOrFail();
        $ortho = Specialty::query()->where('slug', 'orthopedics')->firstOrFail();
        $dental = Specialty::query()->where('slug', 'dentistry')->firstOrFail();

        $a = Provider::factory()->forClinic($clinic)->create([
            'display_name' => 'Alpha Physio',
            'type' => 'physiotherapist',
            'years_experience' => 10,
            'verification_status' => 'verified',
            'is_public' => true,
            'status' => 'active',
            'languages' => ['en'],
        ]);
        $a->specialties()->sync([$physio->id => ['is_primary' => true]]);
        Schedule::factory()->forProvider($a)->create(['is_active' => true]);

        $b = Provider::factory()->forClinic($clinic)->create([
            'display_name' => 'Beta Ortho',
            'type' => 'doctor',
            'years_experience' => 4,
            'verification_status' => 'pending',
            'is_public' => true,
            'status' => 'active',
            'languages' => ['en'],
        ]);
        $b->specialties()->sync([$ortho->id => ['is_primary' => true]]);

        $c = Provider::factory()->forClinic($clinic)->create([
            'display_name' => 'Charlie Dental',
            'type' => 'doctor',
            'years_experience' => 20,
            'verification_status' => 'verified',
            'is_public' => true,
            'status' => 'active',
        ]);
        $c->specialties()->sync([$dental->id => ['is_primary' => true]]);

        $service = app(ProviderRankingService::class);
        $criteria = [
            'care_category' => 'orthopedic_or_physiotherapy',
            'complaint' => 'knee_pain',
        ];

        $first = $service->rank($user->tenant_id, $criteria);
        $second = $service->rank($user->tenant_id, $criteria);

        $this->assertSame(
            array_map(fn ($r) => [$r['id'], $r['score']], $first),
            array_map(fn ($r) => [$r['id'], $r['score']], $second),
        );
        $this->assertSame($first[0]['id'], $a->id);
        $this->assertTrue($first[0]['specialty_match']);
    }
}
