<?php

namespace Tests\Feature;

use App\Models\GuestSession;
use App\Models\HealthGuideConversation;
use App\Models\HealthGuideMessage;
use App\Models\Tenant;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SafetyRulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestHealthGuideTest extends TestCase
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

    public function test_guest_can_start_session_and_chat_without_login(): void
    {
        $start = $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson('/api/v1/public/health-guide/sessions', [
                'anonymous_id' => 'anon-1',
                'locale' => 'en',
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_turns', 0)
            ->assertJsonPath('data.auth_required', false);

        $guestUuid = $start->json('data.guest_session.uuid');
        $this->assertNotEmpty($start->json('data.opening_message.content'));

        $reply = $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson("/api/v1/public/health-guide/sessions/{$guestUuid}/messages", [
                'content' => 'My lower back has been hurting for the last week.',
            ])
            ->assertOk()
            ->assertJsonPath('user_turns', 1)
            ->assertJsonPath('auth_required', false);

        $this->assertSame(HealthGuideMessage::ROLE_ASSISTANT, $reply->json('message.role'));
        $this->assertDatabaseHas('guest_sessions', ['uuid' => $guestUuid]);
    }

    public function test_guest_limit_triggers_continue_prompt(): void
    {
        config(['health_guide.guest.max_user_turns' => 3]);

        $start = $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson('/api/v1/public/health-guide/sessions')
            ->assertCreated();

        $guestUuid = $start->json('data.guest_session.uuid');

        foreach ([
            'Mostly on the right side of my lower back.',
            'It started gradually over a week.',
            'Sitting for a long time makes it worse.',
        ] as $index => $content) {
            $response = $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
                ->postJson("/api/v1/public/health-guide/sessions/{$guestUuid}/messages", [
                    'content' => $content,
                ])
                ->assertOk();

            if ($index < 2) {
                $response->assertJsonPath('auth_required', false);
            } else {
                $response->assertJsonPath('auth_required', true)
                    ->assertJsonStructure(['continue' => ['message', 'options']]);
            }
        }

        $this->withHeaders(['X-Tenant-Slug' => 'healthassist-demo'])
            ->postJson("/api/v1/public/health-guide/sessions/{$guestUuid}/messages", [
                'content' => 'One more question without login.',
            ])
            ->assertForbidden()
            ->assertJsonPath('auth_required', true);

        $this->assertDatabaseHas('health_guide_conversations', [
            'uuid' => $start->json('data.conversation.uuid'),
            'status' => HealthGuideConversation::STATUS_AWAITING_AUTH,
        ]);
    }

    public function test_guest_session_requires_known_tenant(): void
    {
        $this->withHeaders(['X-Tenant-Slug' => 'missing-tenant'])
            ->postJson('/api/v1/public/health-guide/sessions')
            ->assertStatus(422);
    }
}
