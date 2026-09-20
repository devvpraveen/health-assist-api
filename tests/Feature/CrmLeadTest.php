<?php

namespace Tests\Feature;

use App\Models\MarketingLead;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class CrmLeadTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_staff_crm_lead_crud(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $create = $this->postJson('/api/v1/crm/leads', [
            'name' => 'Jamie Lead',
            'email' => 'jamie@example.com',
            'phone' => '+15551212',
            'source' => 'landing',
            'campaign' => 'spring',
        ])->assertCreated()
            ->assertJsonPath('data.email', 'jamie@example.com')
            ->assertJsonPath('data.status', 'new');

        $id = $create->json('data.id');

        $this->patchJson('/api/v1/crm/leads/'.$id, [
            'status' => MarketingLead::STATUS_CONTACTED,
        ])->assertOk()
            ->assertJsonPath('data.status', 'contacted');

        $this->getJson('/api/v1/crm/leads')
            ->assertOk()
            ->assertJsonFragment(['email' => 'jamie@example.com']);

        $this->deleteJson('/api/v1/crm/leads/'.$id)
            ->assertNoContent();
    }

    public function test_public_lead_capture_requires_tenant_and_rejects_clinical_fields_silently(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();

        $this->postJson('/api/v1/public/leads', [
            'tenant_id' => $tenant->id,
            'name' => 'Public Lead',
            'email' => 'public.lead@example.com',
            'source' => 'web',
            'diagnosis' => 'should-not-be-stored',
        ])->assertCreated()
            ->assertJsonPath('data.email', 'public.lead@example.com');

        $lead = MarketingLead::query()->withoutGlobalScopes()->where('email', 'public.lead@example.com')->first();
        $this->assertNotNull($lead);
        $this->assertNull(data_get($lead->getAttributes(), 'diagnosis'));
        $this->assertSame($tenant->id, $lead->tenant_id);
        unset($user);
    }

    public function test_public_lead_honeypot_is_ignored(): void
    {
        [, , $tenant] = $this->createTenantUserWithOrg();

        $this->postJson('/api/v1/public/leads', [
            'tenant_id' => $tenant->id,
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'website' => 'http://spam.test',
        ])->assertCreated()
            ->assertJsonPath('data.accepted', true);

        $this->assertDatabaseMissing('marketing_leads', [
            'email' => 'bot@example.com',
        ]);
    }
}
