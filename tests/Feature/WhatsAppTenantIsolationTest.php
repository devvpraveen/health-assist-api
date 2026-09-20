<?php

namespace Tests\Feature;

use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class WhatsAppTenantIsolationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_tenant_cannot_list_other_tenant_accounts_or_conversations(): void
    {
        [$userA] = $this->createTenantUserWithOrg('Tenant A');
        [$userB] = $this->createTenantUserWithOrg('Tenant B');

        TenantContext::set($userA->tenant_id);
        $accountA = WhatsAppAccount::factory()->forTenant($userA->tenant)->create(['name' => 'A WA']);
        $conversationA = WhatsAppConversation::factory()->forAccount($accountA)->create();
        TenantContext::clear();

        TenantContext::set($userB->tenant_id);
        WhatsAppAccount::factory()->forTenant($userB->tenant)->create(['name' => 'B WA']);
        TenantContext::clear();

        Sanctum::actingAs($userB);
        TenantContext::set($userB->tenant_id);

        $accounts = $this->getJson('/api/v1/whatsapp/accounts')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $accounts);
        $this->assertSame('B WA', $accounts[0]['name']);

        $this->getJson('/api/v1/whatsapp/accounts/'.$accountA->uuid)->assertNotFound();
        $this->getJson('/api/v1/whatsapp/conversations/'.$conversationA->uuid)->assertNotFound();

        $conversations = $this->getJson('/api/v1/whatsapp/conversations')
            ->assertOk()
            ->json('data');

        $this->assertCount(0, $conversations);
    }
}
