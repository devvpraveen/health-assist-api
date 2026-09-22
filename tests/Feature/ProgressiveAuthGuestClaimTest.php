<?php

namespace Tests\Feature;

use App\Models\GuestSession;
use App\Models\HealthGuideMessage;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SafetyRulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProgressiveAuthGuestClaimTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);
        $this->seed(SafetyRulesSeeder::class);

        $this->tenant = Tenant::factory()->create([
            'name' => 'Health Assist Demo',
            'slug' => 'healthassist-demo',
        ]);
    }

    public function test_mobile_otp_stub_verifies_and_migrates_guest_conversation(): void
    {
        config(['health_guide.guest.max_user_turns' => 3]);

        $start = $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson('/api/v1/public/health-guide/sessions')
            ->assertCreated();
        $guestUuid = $start->json('data.guest_session.uuid');

        $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson("/api/v1/public/health-guide/sessions/{$guestUuid}/messages", [
                'content' => 'Lower back pain on the right side.',
            ])->assertOk();
        $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson("/api/v1/public/health-guide/sessions/{$guestUuid}/messages", [
                'content' => 'It started gradually.',
            ])->assertOk();
        $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson("/api/v1/public/health-guide/sessions/{$guestUuid}/messages", [
                'content' => 'Sitting makes it worse.',
            ])->assertOk()->assertJsonPath('auth_required', true);

        $otp = $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson('/api/v1/auth/otp/mobile/request', [
                'mobile' => '+15551234567',
            ])
            ->assertOk();

        $auth = $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson('/api/v1/auth/otp/mobile/verify', [
                'challenge_uuid' => $otp->json('data.challenge_uuid'),
                'code' => $otp->json('data.debug_code'),
                'guest_session_id' => $guestUuid,
            ])
            ->assertOk()
            ->assertJsonPath('migration.migrated', true)
            ->assertJsonPath('migration.message', 'My conversation has been saved.');

        $this->assertNotEmpty($auth->json('token'));
        $conversationUuid = $auth->json('migration.conversation_uuid');

        $user = User::query()->where('phone', '+15551234567')->first();
        $this->assertNotNull($user);
        $this->assertSame($this->tenant->id, $user->tenant_id);
        $this->assertDatabaseHas('patients', [
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
        ]);

        Sanctum::actingAs($user);
        $show = $this->getJson("/api/v1/health-guide/conversations/{$conversationUuid}")
            ->assertOk();

        $roles = collect($show->json('data.messages'))->pluck('role');
        $this->assertTrue($roles->contains(HealthGuideMessage::ROLE_USER));
        $this->assertGreaterThanOrEqual(4, $roles->count());
    }

    public function test_email_otp_and_google_stub_work(): void
    {
        $emailOtp = $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson('/api/v1/auth/otp/email/request', [
                'email' => 'guest@example.com',
            ])->assertOk();

        $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson('/api/v1/auth/otp/email/verify', [
                'challenge_uuid' => $emailOtp->json('data.challenge_uuid'),
                'code' => '123456',
            ])
            ->assertOk()
            ->assertJsonPath('user.email', 'guest@example.com');

        $google = $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson('/api/v1/auth/google', [
                'id_token' => 'Ada Lovelace <ada@gmail.dev>',
            ])
            ->assertOk()
            ->assertJsonPath('user.email', 'ada@gmail.dev');

        $this->assertNotEmpty($google->json('token'));
    }

    public function test_firebase_phone_stub_issues_session(): void
    {
        config(['auth_providers.firebase.api_key' => '']);
        config(['auth_providers.firebase.stub_allowed' => true]);

        $response = $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson('/api/v1/auth/firebase', [
                'id_token' => 'firebase:+919876543210',
            ])
            ->assertOk();

        $this->assertNotEmpty($response->json('token'));
        $this->assertSame('+919876543210', $response->json('user.phone'));
    }

    public function test_wrong_otp_is_rejected(): void
    {
        $otp = $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson('/api/v1/auth/otp/email/request', [
                'email' => 'wrong@example.com',
            ])->assertOk();

        $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson('/api/v1/auth/otp/email/verify', [
                'challenge_uuid' => $otp->json('data.challenge_uuid'),
                'code' => '000000',
            ])
            ->assertStatus(422);
    }

    public function test_authenticated_claim_endpoint_is_idempotent(): void
    {
        $start = $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson('/api/v1/public/health-guide/sessions')
            ->assertCreated();
        $guestUuid = $start->json('data.guest_session.uuid');

        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        Sanctum::actingAs($user);

        $first = $this->postJson('/api/v1/auth/guest/claim', [
            'guest_session_id' => $guestUuid,
        ])->assertOk()->assertJsonPath('data.migrated', true);

        $second = $this->postJson('/api/v1/auth/guest/claim', [
            'guest_session_id' => $guestUuid,
        ])->assertOk()->assertJsonPath('data.migrated', false);

        $this->assertSame(
            $first->json('data.conversation.uuid'),
            $second->json('data.conversation.uuid'),
        );
        $this->assertDatabaseCount('patients', 1);
        $this->assertInstanceOf(GuestSession::class, GuestSession::query()->where('uuid', $guestUuid)->first());
        $this->assertInstanceOf(Patient::class, Patient::query()->where('user_id', $user->id)->first());
    }

    public function test_claim_already_taken_by_another_user_soft_fails(): void
    {
        $start = $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson('/api/v1/public/health-guide/sessions')
            ->assertCreated();
        $guestUuid = $start->json('data.guest_session.uuid');

        $owner = User::factory()->create(['tenant_id' => $this->tenant->id]);
        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/auth/guest/claim', [
            'guest_session_id' => $guestUuid,
        ])->assertOk()->assertJsonPath('data.migrated', true);

        $other = User::factory()->create(['tenant_id' => $this->tenant->id]);
        Sanctum::actingAs($other);
        $this->postJson('/api/v1/auth/guest/claim', [
            'guest_session_id' => $guestUuid,
        ])->assertOk()->assertJsonPath('data.migrated', false);
    }
}
