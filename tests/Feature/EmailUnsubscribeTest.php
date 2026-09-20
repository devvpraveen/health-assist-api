<?php

namespace Tests\Feature;

use App\Models\EmailSubscription;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class EmailUnsubscribeTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_consent_opt_in_and_unsubscribe_by_token(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/email/subscriptions', [
            'email' => 'optin@example.com',
            'consented' => true,
        ])->assertCreated()
            ->assertJsonPath('data.email', 'optin@example.com');

        $subscription = EmailSubscription::query()
            ->where('email', 'optin@example.com')
            ->firstOrFail();

        $this->assertNotNull($subscription->consented_at);
        $this->assertNull($subscription->unsubscribed_at);

        $this->postJson('/api/v1/public/email/unsubscribe?token='.$subscription->unsubscribe_token)
            ->assertOk()
            ->assertJsonPath('data.unsubscribed', true);

        $this->assertNotNull($subscription->refresh()->unsubscribed_at);
    }

    public function test_invalid_unsubscribe_token_fails(): void
    {
        $this->postJson('/api/v1/public/email/unsubscribe?token=not-a-real-token')
            ->assertStatus(422);
    }
}
