<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\PatientPhoneMatcher;
use App\Support\TenantContext;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SafetyRulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class WhatsAppPatientMatchTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);
        $this->seed(SafetyRulesSeeder::class);
    }

    public function test_matches_patient_by_phone_digits_ignoring_formatting(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        TenantContext::set($user->tenant_id);

        $patient = Patient::factory()->forTenant($user->tenant)->create([
            'phone' => '+55 (11) 98765-4321',
        ]);

        $matcher = app(PatientPhoneMatcher::class);
        $matched = $matcher->findForTenant($user->tenant_id, '5511987654321');

        $this->assertNotNull($matched);
        $this->assertSame($patient->id, $matched->id);
    }

    public function test_does_not_match_across_tenants(): void
    {
        [$userA] = $this->createTenantUserWithOrg('A');
        [$userB] = $this->createTenantUserWithOrg('B');

        TenantContext::set($userA->tenant_id);
        Patient::factory()->forTenant($userA->tenant)->create([
            'phone' => '5511987654321',
        ]);

        $matcher = app(PatientPhoneMatcher::class);
        $this->assertNull($matcher->findForTenant($userB->tenant_id, '5511987654321'));
    }

    public function test_inbound_webhook_links_matched_patient(): void
    {
        Http::fake([
            'evolution.test/*' => Http::response(['key' => ['id' => 'm-out']], 200),
        ]);

        [$user] = $this->createTenantUserWithOrg();
        TenantContext::set($user->tenant_id);

        $account = WhatsAppAccount::factory()->forTenant($user->tenant)->create([
            'webhook_secret' => 'secret',
        ]);
        $patient = Patient::factory()->forTenant($user->tenant)->create([
            'phone' => '11987654321',
        ]);

        TenantContext::clear();

        $this->postJson('/api/v1/webhooks/evolution/'.$account->uuid, [
            'event' => 'MESSAGES_UPSERT',
            'data' => [
                'key' => [
                    'remoteJid' => '5511987654321@s.whatsapp.net',
                    'fromMe' => false,
                    'id' => 'm1',
                ],
                'message' => ['conversation' => 'hello'],
            ],
        ], ['apikey' => 'secret'])->assertOk();

        $this->assertDatabaseHas('whatsapp_conversations', [
            'account_id' => $account->id,
            'patient_id' => $patient->id,
            'remote_phone' => '5511987654321',
        ]);
    }
}
