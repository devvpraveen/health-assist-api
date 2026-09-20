<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Specialty;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class PublicClinicDirectoryTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SpecialtySeeder::class);
    }

    public function test_only_public_active_clinics_are_listed(): void
    {
        [, $org] = $this->createTenantUserWithOrg();

        $public = Clinic::factory()->forOrganization($org)->public()->create([
            'name' => 'Public Care',
            'slug' => 'public-care',
            'city' => 'Bengaluru',
        ]);

        Clinic::factory()->forOrganization($org)->create([
            'name' => 'Private Care',
            'slug' => 'private-care',
            'is_public' => false,
            'city' => 'Bengaluru',
        ]);

        Clinic::factory()->forOrganization($org)->create([
            'name' => 'Inactive Public',
            'slug' => 'inactive-public',
            'is_public' => true,
            'status' => 'inactive',
            'city' => 'Bengaluru',
        ]);

        $response = $this->getJson('/api/v1/public/clinics');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $public->uuid)
            ->assertJsonMissing(['slug' => 'private-care']);
    }

    public function test_filters_by_specialty_and_city(): void
    {
        [, $org] = $this->createTenantUserWithOrg();
        $physio = Specialty::query()->where('slug', 'physiotherapy')->firstOrFail();
        $dental = Specialty::query()->where('slug', 'dentistry')->firstOrFail();

        $bangalore = Clinic::factory()->forOrganization($org)->public()->create([
            'name' => 'Bangalore Physio',
            'slug' => 'blr-physio',
            'city' => 'Bengaluru',
        ]);
        $bangalore->specialties()->attach($physio->id);

        $mumbai = Clinic::factory()->forOrganization($org)->public()->create([
            'name' => 'Mumbai Dental',
            'slug' => 'mum-dental',
            'city' => 'Mumbai',
        ]);
        $mumbai->specialties()->attach($dental->id);

        $this->getJson('/api/v1/public/clinics?city=Bengaluru')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'blr-physio');

        $this->getJson('/api/v1/public/clinics?specialty=physiotherapy')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $bangalore->uuid);

        $this->getJson('/api/v1/public/clinics/'.$bangalore->uuid)
            ->assertOk()
            ->assertJsonPath('data.name', 'Bangalore Physio')
            ->assertJsonPath('data.city', 'Bengaluru');

        $this->getJson('/api/v1/public/clinics/private-care')
            ->assertNotFound();
    }
}
