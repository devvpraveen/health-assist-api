<?php

namespace Tests\Feature;

use App\Models\Referral;
use App\Models\ReferralCode;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class ReferralCaptureAndConvertTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_capture_and_convert_referral(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $codeResponse = $this->postJson('/api/v1/referrals/codes', [
            'code' => 'FRIEND01',
            'campaign' => 'friends',
            'max_uses' => 10,
        ])->assertCreated();

        $this->assertSame('FRIEND01', $codeResponse->json('data.code'));

        $this->postJson('/api/v1/public/referrals/capture', [
            'code' => 'FRIEND01',
            'anonymous_id' => 'anon-ref-1',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.referred_anonymous_id', 'anon-ref-1');

        $referral = Referral::query()->withoutGlobalScopes()->where('referred_anonymous_id', 'anon-ref-1')->firstOrFail();

        $this->postJson('/api/v1/referrals/'.$referral->id.'/convert', [
            'reward_type' => 'badge',
        ])->assertOk()
            ->assertJsonPath('data.status', 'converted');

        $this->assertDatabaseHas('referral_codes', [
            'code' => 'FRIEND01',
            'uses_count' => 1,
        ]);

        $this->assertDatabaseHas('referral_rewards', [
            'referral_id' => $referral->id,
            'status' => 'granted',
            'type' => 'badge',
        ]);
    }

    public function test_inactive_code_cannot_be_captured(): void
    {
        [$user] = $this->createTenantUserWithOrg();

        ReferralCode::factory()->create([
            'tenant_id' => $user->tenant_id,
            'code' => 'INACTIVE',
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/public/referrals/capture', [
            'code' => 'INACTIVE',
            'anonymous_id' => 'anon-x',
        ])->assertStatus(422);
    }
}
