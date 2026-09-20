<?php

namespace Tests\Feature;

use App\Models\PushDevice;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class PushDeviceRegistrationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_can_register_list_and_unregister_own_device(): void
    {
        [$user] = $this->createTenantUserWithOrg();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $register = $this->postJson('/api/v1/mobile/devices', [
            'token' => 'ExponentPushToken[test-device-abc]',
            'platform' => 'expo',
        ]);

        $register->assertCreated()
            ->assertJsonPath('data.token', 'ExponentPushToken[test-device-abc]')
            ->assertJsonPath('data.platform', 'expo')
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('push_devices', [
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[test-device-abc]',
            'tenant_id' => $user->tenant_id,
        ]);

        $this->getJson('/api/v1/mobile/devices')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.token', 'ExponentPushToken[test-device-abc]');

        $uuid = $register->json('data.uuid');

        $this->deleteJson("/api/v1/mobile/devices/{$uuid}")
            ->assertNoContent();

        $this->assertDatabaseMissing('push_devices', [
            'token' => 'ExponentPushToken[test-device-abc]',
        ]);
    }

    public function test_register_updates_existing_token_for_same_tenant(): void
    {
        [$user] = $this->createTenantUserWithOrg();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/mobile/devices', [
            'token' => 'stable-token-1',
            'platform' => 'ios',
        ])->assertCreated();

        $this->postJson('/api/v1/mobile/devices', [
            'token' => 'stable-token-1',
            'platform' => 'android',
        ])->assertCreated()
            ->assertJsonPath('data.platform', 'android');

        $this->assertDatabaseCount('push_devices', 1);
        $this->assertDatabaseHas('push_devices', [
            'token' => 'stable-token-1',
            'platform' => 'android',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_list_or_delete_another_users_device(): void
    {
        [$owner] = $this->createTenantUserWithOrg('Clinic A');
        [$intruder, , $tenantB] = $this->createTenantUserWithOrg('Clinic B');

        $device = PushDevice::factory()->create([
            'tenant_id' => $owner->tenant_id,
            'user_id' => $owner->id,
            'token' => 'owner-only-token',
        ]);

        Sanctum::actingAs($intruder);
        TenantContext::set($tenantB->id);

        $this->getJson('/api/v1/mobile/devices')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->deleteJson("/api/v1/mobile/devices/{$device->uuid}")
            ->assertNotFound();

        $this->deleteJson('/api/v1/mobile/devices/owner-only-token')
            ->assertNotFound();

        $this->assertDatabaseHas('push_devices', [
            'id' => $device->id,
            'token' => 'owner-only-token',
        ]);
    }

    public function test_unregister_by_token_works_for_owner(): void
    {
        [$user] = $this->createTenantUserWithOrg();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        PushDevice::factory()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'token' => 'delete-by-token-xyz',
        ]);

        $this->deleteJson('/api/v1/mobile/devices/delete-by-token-xyz')
            ->assertNoContent();

        $this->assertDatabaseMissing('push_devices', [
            'token' => 'delete-by-token-xyz',
        ]);
    }

    public function test_guest_cannot_register_device(): void
    {
        $this->postJson('/api/v1/mobile/devices', [
            'token' => 'guest-token',
            'platform' => 'ios',
        ])->assertUnauthorized();
    }
}
