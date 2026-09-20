<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Provider;
use App\Models\Specialty;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class PublicProviderDirectoryTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SpecialtySeeder::class);
    }

    public function test_only_public_providers_listed_and_filters_work(): void
    {
        [, $org] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($org)->public()->create([
            'city' => 'Chennai',
            'slug' => 'chennai-care',
        ]);
        $privateClinic = Clinic::factory()->forOrganization($org)->create([
            'is_public' => false,
            'city' => 'Chennai',
        ]);

        $physio = Specialty::query()->where('slug', 'physiotherapy')->firstOrFail();

        $publicProvider = Provider::factory()->forClinic($clinic)->public()->create([
            'first_name' => 'Kavya',
            'last_name' => 'Iyer',
            'type' => 'physiotherapist',
            'languages' => ['en', 'ta'],
        ]);
        $publicProvider->specialties()->attach($physio->id, ['is_primary' => true]);

        Provider::factory()->forClinic($clinic)->create([
            'is_public' => false,
            'type' => 'doctor',
        ]);

        Provider::factory()->forClinic($privateClinic)->public()->create([
            'type' => 'physiotherapist',
        ]);

        $list = $this->getJson('/api/v1/public/providers');
        $list->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($publicProvider->uuid, $list->json('data.0.uuid'));

        $this->getJson('/api/v1/public/providers?type=physiotherapist')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/public/providers?specialty=physiotherapy')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $publicProvider->uuid);

        $this->getJson('/api/v1/public/providers?city=Chennai')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/public/providers?clinic=chennai-care')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/public/providers?language=ta')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/public/providers/'.$publicProvider->uuid)
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Kavya')
            ->assertJsonPath('data.clinic.slug', 'chennai-care');
    }

    public function test_non_public_provider_hidden_on_show(): void
    {
        [, $org] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($org)->public()->create();
        $provider = Provider::factory()->forClinic($clinic)->create(['is_public' => false]);

        $this->getJson('/api/v1/public/providers/'.$provider->uuid)
            ->assertNotFound();
    }
}
